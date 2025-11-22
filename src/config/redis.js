import { createClient } from 'redis';
import dotenv from 'dotenv';

dotenv.config();

// สร้าง Redis Client
const redisClient = createClient({
  socket: {
    host: process.env.REDIS_HOST || 'localhost',
    port: parseInt(process.env.REDIS_PORT) || 6379
  },
  password: process.env.REDIS_PASSWORD || undefined,
  database: parseInt(process.env.REDIS_DB) || 0
});

// Event Handlers
redisClient.on('connect', () => {
  console.log('✅ Redis: กำลังเชื่อมต่อ...');
});

redisClient.on('ready', () => {
  console.log('✅ Redis: เชื่อมต่อสำเร็จและพร้อมใช้งาน');
});

redisClient.on('error', (err) => {
  console.error('❌ Redis Error:', err);
});

redisClient.on('end', () => {
  console.log('⚠️  Redis: ตัดการเชื่อมต่อแล้ว');
});

// เชื่อมต่อ Redis
await redisClient.connect();

/**
 * ฟังก์ชันสำหรับ Get ข้อมูลจาก Cache
 * @param {string} key - Redis key
 * @returns {Promise<any|null>} - ข้อมูลที่ถูก parse หรือ null
 */
export async function getCache(key) {
  try {
    const data = await redisClient.get(key);
    if (!data) return null;
    return JSON.parse(data);
  } catch (error) {
    console.error(`Error getting cache for key "${key}":`, error);
    return null;
  }
}

/**
 * ฟังก์ชันสำหรับ Set ข้อมูลลง Cache พร้อม TTL
 * @param {string} key - Redis key
 * @param {any} value - ข้อมูลที่จะเก็บ
 * @param {number} ttl - เวลา expire (วินาที)
 * @returns {Promise<boolean>} - สำเร็จหรือไม่
 */
export async function setCache(key, value, ttl = 3600) {
  try {
    await redisClient.setEx(key, ttl, JSON.stringify(value));
    return true;
  } catch (error) {
    console.error(`Error setting cache for key "${key}":`, error);
    return false;
  }
}

/**
 * ฟังก์ชันสำหรับลบข้อมูลจาก Cache
 * @param {string} key - Redis key
 * @returns {Promise<boolean>}
 */
export async function deleteCache(key) {
  try {
    await redisClient.del(key);
    return true;
  } catch (error) {
    console.error(`Error deleting cache for key "${key}":`, error);
    return false;
  }
}

/**
 * ฟังก์ชันสำหรับลบ Cache หลายๆ key ที่มี pattern เดียวกัน
 * @param {string} pattern - Pattern (เช่น "livescore:*")
 * @returns {Promise<number>} - จำนวน key ที่ถูกลบ
 */
export async function deleteCacheByPattern(pattern) {
  try {
    const keys = await redisClient.keys(pattern);
    if (keys.length === 0) return 0;
    
    await redisClient.del(keys);
    return keys.length;
  } catch (error) {
    console.error(`Error deleting cache pattern "${pattern}":`, error);
    return 0;
  }
}

export default redisClient;
