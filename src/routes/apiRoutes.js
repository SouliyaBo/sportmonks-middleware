import express from 'express';
import * as matchController from '../controllers/matchController.js';

const router = express.Router();

/**
 * Health Check
 * GET /api/health
 */
router.get('/health', matchController.healthCheck);

/**
 * Live Scores
 * GET /api/livescores?date=2024-01-01
 */
router.get('/livescores', matchController.getLiveScores);

/**
 * Fixtures by League
 * GET /api/fixtures/league/:leagueId?season=2024
 */
router.get('/fixtures/league/:leagueId', matchController.getFixturesByLeague);

/**
 * Fixtures by Team
 * GET /api/fixtures/team/:teamId?date=2024-01-01
 */
router.get('/fixtures/team/:teamId', matchController.getTeamFixtures);

/**
 * Standings by League
 * GET /api/standings/league/:leagueId?season=2024
 */
router.get('/standings/league/:leagueId', matchController.getStandingsByLeague);

/**
 * Team Detail
 * GET /api/team/:teamId
 */
router.get('/team/:teamId', matchController.getTeamById);

/**
 * Match Detail
 * GET /api/match/:matchId
 */
router.get('/match/:matchId', matchController.getMatchDetail);

export default router;
