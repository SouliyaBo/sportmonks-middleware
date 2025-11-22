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
    
    // SportMonks v3 ใช้ /fixtures endpoint พร้อม filter
    const today = new Date().toISOString().split('T')[0];
    const futureDate = new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const params = {
      include: 'participants;league;venue;round;state',
      'filter[league_id]': leagueId,
      'filter[date_between]': `${today},${futureDate}`
    };
    
    if (season) {
      params['filter[season_id]'] = season;
    }

    const response = await sportMonksAPI.get('/fixtures', { params });

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
