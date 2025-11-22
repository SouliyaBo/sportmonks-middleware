import cron from 'node-cron';
import dotenv from 'dotenv';
import * as sportService from './sportService.js';

dotenv.config();

console.log('🕒 Cron Service เริ่มทำงาน...\n');

/**
 * กำหนด League IDs ที่ต้องการ Pre-fetch
 * ปรับแต่งตาม League ที่เว็บคุณใช้
 */
const POPULAR_LEAGUES = [
  8,    // Premier League
  564,  // La Liga
  384,  // Serie A
  82,   // Bundesliga
  301,  // Ligue 1
  2,    // Champions League
  5,    // Europa League
];

/**
 * Pre-fetch Live Scores ทุก 1 นาที
 */
cron.schedule('* * * * *', async () => {
  const now = new Date().toISOString();
  console.log(`[${now}] 🔄 กำลัง Pre-fetch Live Scores...`);
  
  try {
    await sportService.getLiveScores('today');
    console.log(`[${now}] ✅ Pre-fetch Live Scores สำเร็จ`);
  } catch (error) {
    console.error(`[${now}] ❌ Pre-fetch Live Scores ล้มเหลว:`, error.message);
  }
});

/**
 * Pre-fetch Fixtures ของ Popular Leagues ทุก 30 นาที
 */
cron.schedule('*/30 * * * *', async () => {
  const now = new Date().toISOString();
  console.log(`[${now}] 🔄 กำลัง Pre-fetch Fixtures...`);
  
  for (const leagueId of POPULAR_LEAGUES) {
    try {
      await sportService.getFixturesByLeague(leagueId);
      console.log(`[${now}] ✅ Pre-fetch Fixtures สำเร็จ - League ${leagueId}`);
    } catch (error) {
      console.error(`[${now}] ❌ Pre-fetch Fixtures ล้มเหลว - League ${leagueId}:`, error.message);
    }
    
    // หน่วงเวลา 2 วินาที เพื่อไม่ให้ยิง API พร้อมกัน
    await new Promise(resolve => setTimeout(resolve, 2000));
  }
});

/**
 * Pre-fetch Standings ของ Popular Leagues ทุก 6 ชั่วโมง
 */
cron.schedule('0 */6 * * *', async () => {
  const now = new Date().toISOString();
  console.log(`[${now}] 🔄 กำลัง Pre-fetch Standings...`);
  
  for (const leagueId of POPULAR_LEAGUES) {
    try {
      await sportService.getStandingsByLeague(leagueId);
      console.log(`[${now}] ✅ Pre-fetch Standings สำเร็จ - League ${leagueId}`);
    } catch (error) {
      console.error(`[${now}] ❌ Pre-fetch Standings ล้มเหลว - League ${leagueId}:`, error.message);
    }
    
    // หน่วงเวลา 2 วินาที
    await new Promise(resolve => setTimeout(resolve, 2000));
  }
});

/**
 * ลบ Cache ที่หมดอายุทุกวันเที่ยงคืน
 */
cron.schedule('0 0 * * *', async () => {
  const now = new Date().toISOString();
  console.log(`[${now}] 🧹 กำลังทำความสะอาด Cache...`);
  
  try {
    // Redis จะลบ Cache ที่หมดอายุอัตโนมัติ
    // แต่สามารถเพิ่มการลบ pattern เฉพาะได้ตามต้องการ
    console.log(`[${now}] ✅ ทำความสะอาด Cache เรียบร้อย`);
  } catch (error) {
    console.error(`[${now}] ❌ ทำความสะอาด Cache ล้มเหลว:`, error.message);
  }
});

console.log('✅ Cron Jobs ถูกตั้งค่าเรียบร้อยแล้ว:');
console.log('   - Live Scores: ทุก 1 นาที');
console.log('   - Fixtures: ทุก 30 นาที');
console.log('   - Standings: ทุก 6 ชั่วโมง');
console.log('   - Cache Cleanup: ทุกวันเที่ยงคืน\n');

// Keep process running
process.on('SIGTERM', () => {
  console.log('⚠️  SIGTERM signal received: stopping cron service');
  process.exit(0);
});
