/**
 * Data Transformer - แปลงข้อมูลจาก SportMonks ให้เหลือแค่ที่จำเป็น
 * ลดขนาดข้อมูลและทำให้ WordPress ใช้งานง่ายขึ้น
 */

/**
 * แปลงข้อมูล Live Score
 * @param {Array} matches - ข้อมูล matches จาก SportMonks
 * @returns {Array} - ข้อมูลที่ถูกกรองแล้ว
 */
export function transformLiveScores(matches) {
  if (!Array.isArray(matches)) return [];

  return matches.map(match => ({
    id: match.id,
    league: {
      id: match.league?.id,
      name: match.league?.name,
      logo: match.league?.image_path
    },
    homeTeam: {
      id: match.participants?.[0]?.id,
      name: match.participants?.[0]?.name,
      short_code: match.participants?.[0]?.short_code,
      logo: match.participants?.[0]?.image_path
    },
    awayTeam: {
      id: match.participants?.[1]?.id,
      name: match.participants?.[1]?.name,
      short_code: match.participants?.[1]?.short_code,
      logo: match.participants?.[1]?.image_path
    },
    score: {
      home: match.scores?.[0]?.score?.goals || 0,
      away: match.scores?.[1]?.score?.goals || 0
    },
    status: {
      name: match.state?.state,
      short: match.state?.short_name,
      minute: match.state?.minute || null
    },
    startTime: match.starting_at,
    venue: match.venue?.name || null
  }));
}

/**
 * แปลงข้อมูลตารางแข่งขัน (Fixtures)
 * @param {Array} fixtures - ข้อมูล fixtures จาก SportMonks
 * @returns {Array}
 */
export function transformFixtures(fixtures) {
  if (!Array.isArray(fixtures)) return [];

  return fixtures.map(fixture => ({
    id: fixture.id,
    league: {
      id: fixture.league?.id,
      name: fixture.league?.name,
      logo: fixture.league?.image_path
    },
    homeTeam: {
      id: fixture.participants?.[0]?.id,
      name: fixture.participants?.[0]?.name,
      logo: fixture.participants?.[0]?.image_path
    },
    awayTeam: {
      id: fixture.participants?.[1]?.id,
      name: fixture.participants?.[1]?.name,
      logo: fixture.participants?.[1]?.image_path
    },
    startTime: fixture.starting_at,
    venue: fixture.venue?.name || null,
    round: fixture.round?.name || null
  }));
}

/**
 * แปลงข้อมูลตารางคะแนน (Standings)
 * @param {Array} standings - ข้อมูล standings จาก SportMonks
 * @returns {Array}
 */
export function transformStandings(standings) {
  if (!Array.isArray(standings)) return [];

  return standings.map(standing => ({
    position: standing.position,
    team: {
      id: standing.participant?.id,
      name: standing.participant?.name,
      logo: standing.participant?.image_path
    },
    stats: {
      played: standing.details?.[0]?.value || 0,
      won: standing.details?.[1]?.value || 0,
      draw: standing.details?.[2]?.value || 0,
      lost: standing.details?.[3]?.value || 0,
      goalsFor: standing.details?.[4]?.value || 0,
      goalsAgainst: standing.details?.[5]?.value || 0,
      goalDifference: standing.details?.[6]?.value || 0,
      points: standing.points || 0
    },
    form: standing.form || null
  }));
}

/**
 * แปลงข้อมูลทีม
 * @param {Object} team - ข้อมูลทีมจาก SportMonks
 * @returns {Object}
 */
export function transformTeam(team) {
  if (!team) return null;

  return {
    id: team.id,
    name: team.name,
    short_code: team.short_code,
    logo: team.image_path,
    country: {
      id: team.country?.id,
      name: team.country?.name
    },
    venue: {
      id: team.venue?.id,
      name: team.venue?.name,
      city: team.venue?.city?.name,
      capacity: team.venue?.capacity
    },
    founded: team.founded || null
  };
}

/**
 * แปลงข้อมูลรายละเอียดแมตช์
 * @param {Object} match - ข้อมูลแมตช์จาก SportMonks
 * @returns {Object}
 */
export function transformMatchDetail(match) {
  if (!match) return null;

  return {
    id: match.id,
    league: {
      id: match.league?.id,
      name: match.league?.name,
      logo: match.league?.image_path,
      country: match.league?.country?.name
    },
    homeTeam: {
      id: match.participants?.[0]?.id,
      name: match.participants?.[0]?.name,
      logo: match.participants?.[0]?.image_path
    },
    awayTeam: {
      id: match.participants?.[1]?.id,
      name: match.participants?.[1]?.name,
      logo: match.participants?.[1]?.image_path
    },
    score: {
      home: match.scores?.[0]?.score?.goals || 0,
      away: match.scores?.[1]?.score?.goals || 0,
      halfTime: {
        home: match.scores?.[0]?.score?.participant || 0,
        away: match.scores?.[1]?.score?.participant || 0
      }
    },
    status: {
      name: match.state?.state,
      short: match.state?.short_name,
      minute: match.state?.minute || null
    },
    startTime: match.starting_at,
    venue: {
      name: match.venue?.name,
      city: match.venue?.city?.name
    },
    referee: match.referee?.common_name || null,
    events: match.events?.map(event => ({
      type: event.type?.name,
      minute: event.minute,
      player: event.player?.display_name,
      team: event.participant?.name
    })) || [],
    statistics: match.statistics || []
  };
}
