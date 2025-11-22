import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import compression from 'compression';
import rateLimit from 'express-rate-limit';
import dotenv from 'dotenv';
import apiRoutes from './src/routes/apiRoutes.js';

// โหลด environment variables
dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// ===== Middlewares =====

// 1. Helmet - เพิ่มความปลอดภัยด้วย HTTP Headers
app.use(helmet());

// 2. CORS - อนุญาตให้ WordPress เข้าถึง API
const allowedOrigins = process.env.ALLOWED_ORIGINS?.split(',') || ['http://localhost:8000'];
app.use(cors({
  origin: function (origin, callback) {
    // อนุญาตให้ request ที่ไม่มี origin (เช่น Postman, mobile apps)
    if (!origin) return callback(null, true);
    
    if (allowedOrigins.indexOf(origin) !== -1) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  },
  credentials: true
}));

// 3. Compression - บีบอัดข้อมูลก่อนส่ง
app.use(compression());

// 4. JSON Parser
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 5. Rate Limiting - ป้องกัน Abuse
const limiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000, // 15 นาที
  max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100, // จำกัด 100 requests
  message: {
    success: false,
    message: 'คำขอมากเกินไป กรุณาลองใหม่อีกครั้งภายหลัง'
  },
  standardHeaders: true,
  legacyHeaders: false
});

app.use('/api/', limiter);

// ===== Routes =====

// API Routes
app.use('/api', apiRoutes);

// Root endpoint
app.get('/', (req, res) => {
  res.json({
    message: 'SportMonks Middleware API',
    version: '1.0.0',
    endpoints: {
      health: '/api/health',
      liveScores: '/api/livescores',
      fixtures: {
        byLeague: '/api/fixtures/league/:leagueId',
        byTeam: '/api/fixtures/team/:teamId'
      },
      standings: '/api/standings/league/:leagueId',
      team: '/api/team/:teamId',
      match: '/api/match/:matchId'
    },
    documentation: 'https://docs.sportmonks.com'
  });
});

// 404 Handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint ไม่พบในระบบ'
  });
});

// Global Error Handler
app.use((err, req, res, next) => {
  console.error('Global Error Handler:', err.stack);
  
  res.status(err.status || 500).json({
    success: false,
    message: err.message || 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์',
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
});

// ===== Start Server =====

const server = app.listen(PORT, () => {
  console.log('\n🚀 ========================================');
  console.log(`🟢 Server กำลังทำงานที่ http://localhost:${PORT}`);
  console.log(`🟢 Environment: ${process.env.NODE_ENV || 'development'}`);
  console.log(`🟢 Redis Host: ${process.env.REDIS_HOST}:${process.env.REDIS_PORT}`);
  console.log('🚀 ========================================\n');
});

// Graceful Shutdown
process.on('SIGTERM', () => {
  console.log('⚠️  SIGTERM signal received: closing HTTP server');
  server.close(() => {
    console.log('✅ HTTP server closed');
    process.exit(0);
  });
});

process.on('SIGINT', () => {
  console.log('⚠️  SIGINT signal received: closing HTTP server');
  server.close(() => {
    console.log('✅ HTTP server closed');
    process.exit(0);
  });
});

export default app;
