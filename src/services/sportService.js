import axios from 'axios';
import dotenv from 'dotenv';
import { getCache, setCache } from '../config/redis.js';
import {
  transformLiveScores,
  transformFixtures,
  transformStandings,
  transformTeam,
  transformMatchDetail
} from '../utils/dataTransformer.js';

dotenv.config();

const SPORTMONKS_API_KEY = process.env.SPORTMONKS_API_KEY;
const SPORTMONKS_BASE_URL = process.env.SPORTMONKS_BASE_URL;

// TTL จาก .env
const TTL = {
  LIVESCORES: parseInt(process.env.CACHE_TTL_LIVESCORES) || 60,
  FIXTURES: parseInt(process.env.CACHE_TTL_FIXTURES) || 3600,
  STANDINGS: parseInt(process.env.CACHE_TTL_STANDINGS) || 86400,
  TEAMS: parseInt(process.env.CACHE_TTL_TEAMS) || 604800
};

/**
 * สร้าง Axios instance พร้อม config
 */
const sportMonksAPI = axios.create({
  baseURL: SPORTMONKS_BASE_URL,
  params: {
    api_token: SPORTMONKS_API_KEY
  },
  timeout: 10000
});

/**
 * ดึงข้อมูล Live Scores (อัปเดตทุก 1 นาที)
 * @param {string} date - วันที่ (YYYY-MM-DD) หรือ 'today'
 * @returns {Promise<Array>}
 */
export async function getLiveScores(date = 'today') {
  const cacheKey = `livescores:${date}`;

  try {
    // 1. ตรวจสอบ Cache ก่อน
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    // 2. ถ้าไม่มี Cache ให้ดึงจาก SportMonks
    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get('/livescores', {
      params: {
        include: 'participants;league;scores;state;venue'
      }
    });

    // 3. แปลงข้อมูลให้เหลือแค่ที่จำเป็น
    const transformedData = transformLiveScores(response.data.data);

    // 4. เก็บลง Cache
    await setCache(cacheKey, transformedData, TTL.LIVESCORES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.LIVESCORES}s)`);

    return transformedData;
  } catch (error) {
    console.error('❌ Error fetching live scores:', error.message);
    throw new Error('ไม่สามารถดึงข้อมูล Live Scores ได้');
  }
}

/**
 * ดึงตารางแข่งขันของลีก (อัปเดตทุก 1 ชั่วโมง)
 * @param {number} leagueId - ID ของลีก
 * @param {string} season - ซีซั่น (เช่น 2024)
 * @returns {Promise<Array>}
 */
export async function getFixturesByLeague(leagueId, season = null) {
  const cacheKey = `fixtures:league:${leagueId}:${season || 'current'}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    
    // ตาม documentation: ใช้ fixtures endpoint โดยไม่ต้อง filter
    // แต่ใช้ season endpoint แทน
    const endpoint = season 
      ? `/seasons/${season}/fixtures`
      : `/fixtures`;
    
    const params = {
      include: 'participants;league;venue;round;state'
    };
    
    // ถ้าไม่มี season ให้ดึงแค่ fixtures ที่มา league นี้
    if (!season) {
      // ใช้ livescores แล้วกรองเอาเอง
      const allFixtures = await sportMonksAPI.get('/livescores/inplay', { 
        params: { include: 'participants;league;venue;round;state' }
      });
      
      // กรองเฉพาะ league ที่ต้องการ
      const filtered = allFixtures.data.data.filter(f => f.league?.id === parseInt(leagueId));
      return filtered;
    }

    const response = await sportMonksAPI.get(endpoint, { params });

    const transformedData = transformFixtures(response.data.data);
    await setCache(cacheKey, transformedData, TTL.FIXTURES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.FIXTURES}s)`);

    return transformedData;
  } catch (error) {
    console.error('❌ Error fetching fixtures:', error.message);
    throw new Error('ไม่สามารถดึงตารางแข่งขันได้');
  }
}

/**
 * ดึงตารางคะแนนของลีก (อัปเดตวันละ 1 ครั้ง)
 * @param {number} leagueId - ID ของลีก
 * @param {string} season - ซีซั่น
 * @returns {Promise<Array>}
 */
export async function getStandingsByLeague(leagueId, season = null) {
  const cacheKey = `standings:league:${leagueId}:${season || 'current'}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const endpoint = season
      ? `/standings/seasons/${season}`
      : `/leagues/${leagueId}/standings`;

    const response = await sportMonksAPI.get(endpoint, {
      params: {
        include: 'participant;details'
      }
    });

    const transformedData = transformStandings(response.data.data);
    await setCache(cacheKey, transformedData, TTL.STANDINGS);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.STANDINGS}s)`);

    return transformedData;
  } catch (error) {
    console.error('❌ Error fetching standings:', error.message);
    throw new Error('ไม่สามารถดึงตารางคะแนนได้');
  }
}

/**
 * ดึงข้อมูลทีม (Cache นาน 7 วัน เพราะแทบไม่เปลี่ยน)
 * @param {number} teamId - ID ของทีม
 * @returns {Promise<Object>}
 */
export async function getTeamById(teamId) {
  const cacheKey = `team:${teamId}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get(`/teams/${teamId}`, {
      params: {
        include: 'country;venue;venue.city'
      }
    });

    const transformedData = transformTeam(response.data.data);
    await setCache(cacheKey, transformedData, TTL.TEAMS);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.TEAMS}s)`);

    return transformedData;
  } catch (error) {
    console.error('❌ Error fetching team:', error.message);
    throw new Error('ไม่สามารถดึงข้อมูลทีมได้');
  }
}

/**
 * ดึงรายละเอียดแมตช์ (รวม Events, Statistics)
 * @param {number} matchId - ID ของแมตช์
 * @returns {Promise<Object>}
 */
export async function getMatchDetail(matchId) {
  const cacheKey = `match:${matchId}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get(`/fixtures/${matchId}`, {
      params: {
        include: 'participants;league;scores;state;venue;venue.city;events;events.player;events.participant;statistics;referee'
      }
    });

    const transformedData = transformMatchDetail(response.data.data);
    await setCache(cacheKey, transformedData, TTL.LIVESCORES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.LIVESCORES}s)`);

    return transformedData;
  } catch (error) {
    console.error('❌ Error fetching match detail:', error.message);
    throw new Error('ไม่สามารถดึงรายละเอียดแมตช์ได้');
  }
}

/**
 * ดึงแมตช์ของทีมในวันที่กำหนด
 * @param {number} teamId - ID ของทีม
 * @param {string} date - วันที่ (YYYY-MM-DD)
 * @returns {Promise<Array>}
 */
export async function getTeamFixtures(teamId, date = null) {
  const cacheKey = `fixtures:team:${teamId}:${date || 'upcoming'}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const endpoint = `/teams/${teamId}/fixtures`;
    const params = {
      include: 'participants;league;venue;scores;state'
    };

    if (date) {
      params.date = date;
    }

    const response = await sportMonksAPI.get(endpoint, { params });
    const transformedData = transformFixtures(response.data.data);
    await setCache(cacheKey, transformedData, TTL.FIXTURES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.FIXTURES}s)`);

    return transformedData;
  } catch (error) {
    console.error('❌ Error fetching team fixtures:', error.message);
    throw new Error('ไม่สามารถดึงแมตช์ของทีมได้');
  }
}

/**
 * ดึงตารางการแข่งขันทั้งซีซัน (Schedules)
 * @param {number} seasonId - ID ของซีซัน
 * @returns {Promise<Array>}
 */
export async function getSchedulesBySeason(seasonId) {
  const cacheKey = `schedules:season:${seasonId}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get(`/schedules/seasons/${seasonId}`);

    const schedules = response.data.data;
    await setCache(cacheKey, schedules, TTL.FIXTURES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.FIXTURES}s)`);

    return schedules;
  } catch (error) {
    console.error('❌ Error fetching schedules by season:', error.message);
    throw new Error('ไม่สามารถดึงตารางการแข่งขันได้');
  }
}

/**
 * ดึงตารางการแข่งขันของทีม (ซีซันปัจจุบัน)
 * @param {number} teamId - ID ของทีม
 * @returns {Promise<Array>}
 */
export async function getSchedulesByTeam(teamId) {
  const cacheKey = `schedules:team:${teamId}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get(`/schedules/teams/${teamId}`);

    const schedules = response.data.data;
    await setCache(cacheKey, schedules, TTL.FIXTURES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.FIXTURES}s)`);

    return schedules;
  } catch (error) {
    console.error('❌ Error fetching schedules by team:', error.message);
    throw new Error('ไม่สามารถดึงตารางการแข่งขันของทีมได้');
  }
}

/**
 * ดึงตารางการแข่งขันของทีมในซีซันที่เลือก
 * @param {number} seasonId - ID ของซีซัน
 * @param {number} teamId - ID ของทีม
 * @returns {Promise<Array>}
 */
export async function getSchedulesBySeasonAndTeam(seasonId, teamId) {
  const cacheKey = `schedules:season:${seasonId}:team:${teamId}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get(`/schedules/seasons/${seasonId}/teams/${teamId}`);

    const schedules = response.data.data;
    await setCache(cacheKey, schedules, TTL.FIXTURES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.FIXTURES}s)`);

    return schedules;
  } catch (error) {
    console.error('❌ Error fetching schedules by season and team:', error.message);
    throw new Error('ไม่สามารถดึงตารางการแข่งขันได้');
  }
}

/**
 * ดึงข้อมูลลีกพร้อม Season ปัจจุบัน
 * @param {number} leagueId - ID ของลีก
 * @returns {Promise<Object>}
 */
export async function getLeagueWithCurrentSeason(leagueId) {
  const cacheKey = `league:${leagueId}:currentSeason`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    const response = await sportMonksAPI.get(`/leagues/${leagueId}`, {
      params: {
        include: 'currentSeason'
      }
    });

    const leagueData = response.data.data;
    await setCache(cacheKey, leagueData, TTL.STANDINGS);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.STANDINGS}s)`);

    return leagueData;
  } catch (error) {
    console.error('❌ Error fetching league with current season:', error.message);
    throw new Error('ไม่สามารถดึงข้อมูลลีกและซีซันปัจจุบันได้');
  }
}

/**
 * ดึงตารางการแข่งขันของ Season ปัจจุบันโดยใช้ League ID
 * @param {number} leagueId - ID ของลีก
 * @returns {Promise<Array>}
 */
export async function getCurrentSeasonSchedulesByLeague(leagueId) {
  const cacheKey = `schedules:league:${leagueId}:current`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึง current season แล้วดึง schedules...`);
    
    // 1. ดึง current season ของลีก
    const leagueData = await getLeagueWithCurrentSeason(leagueId);
    const currentSeason = leagueData.currentseason || leagueData.currentSeason;
    
    if (!currentSeason || !currentSeason.id) {
      throw new Error('ไม่พบซีซันปัจจุบันของลีกนี้');
    }

    // 2. ดึง schedules ของ season นั้น
    const response = await sportMonksAPI.get(`/schedules/seasons/${currentSeason.id}`);
    const schedules = response.data.data;

    await setCache(cacheKey, schedules, TTL.FIXTURES);
    console.log(`✅ Cached: ${cacheKey} (Season ID: ${currentSeason.id}, TTL: ${TTL.FIXTURES}s)`);

    return schedules;
  } catch (error) {
    console.error('❌ Error fetching current season schedules:', error.message);
    throw new Error(error.message || 'ไม่สามารถดึงตารางการแข่งขันของซีซันปัจจุบันได้');
  }
}

/**
 * ดึงแมตช์ตามวันที่ และจัดกลุ่มตามลีก
 * @param {string} date - วันที่ (YYYY-MM-DD) หรือ 'today'
 * @returns {Promise<Object>}
 */
export async function getFixturesByDateGroupedByLeague(date = 'today') {
  const cacheKey = `fixtures:bydate:${date}`;

  try {
    const cachedData = await getCache(cacheKey);
    if (cachedData) {
      console.log(`✅ Cache Hit: ${cacheKey}`);
      return cachedData;
    }

    console.log(`⚠️  Cache Miss: ${cacheKey} - กำลังดึงข้อมูลจาก SportMonks...`);
    
    // ดึงข้อมูลแมตช์ทั้งหมดของวันนั้น
    const response = await sportMonksAPI.get('/fixtures/date/' + date, {
      params: {
        include: 'participants;league;venue;scores;state'
      }
    });

    const fixtures = response.data.data;

    // จัดกลุ่มตามลีก
    const groupedByLeague = {};
    
    fixtures.forEach(fixture => {
      const leagueId = fixture.league?.id;
      const leagueName = fixture.league?.name || 'อื่นๆ';
      
      if (!groupedByLeague[leagueId]) {
        groupedByLeague[leagueId] = {
          league: {
            id: leagueId,
            name: leagueName,
            image_path: fixture.league?.image_path,
            country: fixture.league?.country?.name
          },
          fixtures: []
        };
      }
      
      groupedByLeague[leagueId].fixtures.push(fixture);
    });

    // เรียงลำดับแมตช์ในแต่ละลีกตามเวลา
    Object.values(groupedByLeague).forEach(group => {
      group.fixtures.sort((a, b) => {
        return new Date(a.starting_at) - new Date(b.starting_at);
      });
    });

    // แปลง object เป็น array และเรียงตามจำนวนแมตช์
    const result = Object.values(groupedByLeague).sort((a, b) => {
      return b.fixtures.length - a.fixtures.length;
    });

    await setCache(cacheKey, result, TTL.LIVESCORES);
    console.log(`✅ Cached: ${cacheKey} (TTL: ${TTL.LIVESCORES}s)`);

    return result;
  } catch (error) {
    console.error('❌ Error fetching fixtures by date:', error.message);
    throw new Error('ไม่สามารถดึงข้อมูลแมตช์ตามวันที่ได้');
  }
}
