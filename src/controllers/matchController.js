import * as sportService from '../services/sportService.js';

/**
 * Controller สำหรับ Live Scores
 * GET /api/livescores
 * Query params: date (optional)
 */
export async function getLiveScores(req, res) {
  try {
    const { date } = req.query;
    const liveScores = await sportService.getLiveScores(date || 'today');

    res.status(200).json({
      success: true,
      data: liveScores,
      count: liveScores.length,
      cached: true
    });
  } catch (error) {
    console.error('Error in getLiveScores controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล Live Scores'
    });
  }
}

/**
 * Controller สำหรับดึงตารางแข่งขันของลีก
 * GET /api/fixtures/league/:leagueId
 * Query params: season (optional)
 */
export async function getFixturesByLeague(req, res) {
  try {
    const { leagueId } = req.params;
    const { season } = req.query;

    if (!leagueId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ League ID'
      });
    }

    const fixtures = await sportService.getFixturesByLeague(leagueId, season);

    res.status(200).json({
      success: true,
      data: fixtures,
      count: fixtures.length
    });
  } catch (error) {
    console.error('Error in getFixturesByLeague controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงตารางแข่งขัน'
    });
  }
}

/**
 * Controller สำหรับดึงตารางคะแนนของลีก
 * GET /api/standings/league/:leagueId
 * Query params: season (optional)
 */
export async function getStandingsByLeague(req, res) {
  try {
    const { leagueId } = req.params;
    const { season } = req.query;

    if (!leagueId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ League ID'
      });
    }

    const standings = await sportService.getStandingsByLeague(leagueId, season);

    res.status(200).json({
      success: true,
      data: standings,
      count: standings.length
    });
  } catch (error) {
    console.error('Error in getStandingsByLeague controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงตารางคะแนน'
    });
  }
}

/**
 * Controller สำหรับดึงข้อมูลทีม
 * GET /api/team/:teamId
 */
export async function getTeamById(req, res) {
  try {
    const { teamId } = req.params;

    if (!teamId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ Team ID'
      });
    }

    const team = await sportService.getTeamById(teamId);

    res.status(200).json({
      success: true,
      data: team
    });
  } catch (error) {
    console.error('Error in getTeamById controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลทีม'
    });
  }
}

/**
 * Controller สำหรับดึงรายละเอียดแมตช์
 * GET /api/match/:matchId
 */
export async function getMatchDetail(req, res) {
  try {
    const { matchId } = req.params;

    if (!matchId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ Match ID'
      });
    }

    const match = await sportService.getMatchDetail(matchId);

    res.status(200).json({
      success: true,
      data: match
    });
  } catch (error) {
    console.error('Error in getMatchDetail controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงรายละเอียดแมตช์'
    });
  }
}

/**
 * Controller สำหรับดึงแมตช์ของทีม
 * GET /api/fixtures/team/:teamId
 * Query params: date (optional)
 */
export async function getTeamFixtures(req, res) {
  try {
    const { teamId } = req.params;
    const { date } = req.query;

    if (!teamId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ Team ID'
      });
    }

    const fixtures = await sportService.getTeamFixtures(teamId, date);

    res.status(200).json({
      success: true,
      data: fixtures,
      count: fixtures.length
    });
  } catch (error) {
    console.error('Error in getTeamFixtures controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงแมตช์ของทีม'
    });
  }
}

/**
 * Health Check Endpoint
 * GET /api/health
 */
export async function healthCheck(req, res) {
  res.status(200).json({
    success: true,
    message: 'SportMonks Middleware API is running',
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
}

/**
 * Controller สำหรับดึงตารางการแข่งขันทั้งซีซัน
 * GET /api/schedules/season/:seasonId
 */
export async function getSchedulesBySeason(req, res) {
  try {
    const { seasonId } = req.params;

    if (!seasonId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ Season ID'
      });
    }

    const schedules = await sportService.getSchedulesBySeason(seasonId);

    res.status(200).json({
      success: true,
      data: schedules,
      count: Array.isArray(schedules) ? schedules.length : 0
    });
  } catch (error) {
    console.error('Error in getSchedulesBySeason controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงตารางการแข่งขัน'
    });
  }
}

/**
 * Controller สำหรับดึงตารางการแข่งขันของทีม
 * GET /api/schedules/team/:teamId
 */
export async function getSchedulesByTeam(req, res) {
  try {
    const { teamId } = req.params;

    if (!teamId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ Team ID'
      });
    }

    const schedules = await sportService.getSchedulesByTeam(teamId);

    res.status(200).json({
      success: true,
      data: schedules,
      count: Array.isArray(schedules) ? schedules.length : 0
    });
  } catch (error) {
    console.error('Error in getSchedulesByTeam controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงตารางการแข่งขัน'
    });
  }
}

/**
 * Controller สำหรับดึงตารางการแข่งขันของทีมในซีซันที่เลือก
 * GET /api/schedules/season/:seasonId/team/:teamId
 */
export async function getSchedulesBySeasonAndTeam(req, res) {
  try {
    const { seasonId, teamId } = req.params;

    if (!seasonId || !teamId) {
      return res.status(400).json({
        success: false,
        message: 'กรุณาระบุ Season ID และ Team ID'
      });
    }

    const schedules = await sportService.getSchedulesBySeasonAndTeam(seasonId, teamId);

    res.status(200).json({
      success: true,
      data: schedules,
      count: Array.isArray(schedules) ? schedules.length : 0
    });
  } catch (error) {
    console.error('Error in getSchedulesBySeasonAndTeam controller:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'เกิดข้อผิดพลาดในการดึงตารางการแข่งขัน'
    });
  }
}
