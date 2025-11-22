# SportMonks Middleware API

Intermediate Server สำหรับจัดการการเชื่อมต่อระหว่าง WordPress และ SportMonks API พร้อมระบบ Caching ด้วย Redis

## 🎯 วัตถุประสงค์

1. **ลด Rate Limit** - ป้องกันการยิง API ไป SportMonks บ่อยเกินไป
2. **เพิ่มความเร็ว** - Cache ข้อมูลลง Redis ทำให้ WordPress ตอบสนองเร็วขึ้น
3. **ความปลอดภัย** - ซ่อน API Key ไว้ใน Server ไม่ต้องเก็บใน WordPress
4. **แปลงข้อมูล** - กรองข้อมูลที่ไม่จำเป็นออก ส่งแค่ที่ WordPress ต้องการ

## 🏗️ สถาปัตยกรรม

```
User -> WordPress -> Middleware Server (Node.js + Redis) -> SportMonks API
```

## 📁 โครงสร้างโปรเจกต์

```
sportmonks-middleware/
├── .env                      # Environment variables (ห้ามเอาขึ้น Git)
├── .env.example              # ตัวอย่าง environment variables
├── .gitignore
├── package.json
├── server.js                 # Entry point
└── src/
    ├── config/
    │   └── redis.js          # Redis configuration
    ├── controllers/
    │   └── matchController.js # Request handlers
    ├── routes/
    │   └── apiRoutes.js      # API endpoints
    ├── services/
    │   └── sportService.js   # Business logic + Caching
    └── utils/
        └── dataTransformer.js # Data transformation
```

## 🚀 การติดตั้ง

### 1. ติดตั้ง Dependencies

```bash
npm install
```

### 2. ตั้งค่า Environment Variables

คัดลอก `.env.example` เป็น `.env` แล้วแก้ไขค่าต่างๆ:

```bash
cp .env.example .env
```

แก้ไขไฟล์ `.env`:

```env
# Server
PORT=3000
NODE_ENV=production

# SportMonks API
SPORTMONKS_API_KEY=your_actual_api_key_here
SPORTMONKS_BASE_URL=https://api.sportmonks.com/v3/football

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# CORS (WordPress URL)
ALLOWED_ORIGINS=https://yourwordpress.com,http://localhost:8000
```

### 3. ติดตั้งและรัน Redis

**macOS (Homebrew):**
```bash
brew install redis
brew services start redis
```

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl start redis
```

**Docker:**
```bash
docker run -d -p 6379:6379 --name redis redis:latest
```

### 4. รัน Server

**Development Mode (Auto-reload):**
```bash
npm run dev
```

**Production Mode:**
```bash
npm start
```

Server จะรันที่ `http://localhost:3000`

## 📡 API Endpoints

### Health Check
```
GET /api/health
```

### Live Scores
```
GET /api/livescores?date=2024-01-01
```

### Fixtures (ตารางแข่งขัน)
```
GET /api/fixtures/league/:leagueId?season=2024
GET /api/fixtures/team/:teamId?date=2024-01-01
```

### Standings (ตารางคะแนน)
```
GET /api/standings/league/:leagueId?season=2024
```

### Team Detail
```
GET /api/team/:teamId
```

### Match Detail
```
GET /api/match/:matchId
```

## 🔧 การใช้งานใน WordPress

### ตัวอย่าง PHP Code

```php
<?php
function get_live_scores() {
    $api_url = 'http://localhost:3000/api/livescores';
    
    $response = wp_remote_get($api_url, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data['data'] ?? [];
}

// ใช้งาน
$live_scores = get_live_scores();
foreach ($live_scores as $match) {
    echo $match['homeTeam']['name'] . ' vs ' . $match['awayTeam']['name'];
    echo ' (' . $match['score']['home'] . '-' . $match['score']['away'] . ')';
}
?>
```

## 🔐 Security Features

- ✅ **Helmet** - ป้องกัน Common Security Vulnerabilities
- ✅ **CORS** - จำกัดการเข้าถึงจากเฉพาะ WordPress URL
- ✅ **Rate Limiting** - จำกัดจำนวน Requests (100 ครั้ง/15 นาที)
- ✅ **Compression** - บีบอัดข้อมูลก่อนส่ง

## 📊 Cache Strategy

| ประเภทข้อมูล | TTL (เวลา Cache) | เหตุผล |
|-------------|-----------------|--------|
| Live Scores | 60 วินาที | ต้องการความ Real-time |
| Fixtures | 1 ชั่วโมง | ตารางแข่งไม่ค่อยเปลี่ยน |
| Standings | 1 วัน | คะแนนอัปเดตหลังจบแมตช์ |
| Teams | 7 วัน | ข้อมูลทีมแทบไม่เปลี่ยน |

## 🧪 Testing

### ทดสอบด้วย cURL

```bash
# Health Check
curl http://localhost:3000/api/health

# Live Scores
curl http://localhost:3000/api/livescores

# Fixtures ของ Premier League (ID: 8)
curl http://localhost:3000/api/fixtures/league/8

# Team Detail
curl http://localhost:3000/api/team/1
```

## 🎮 WordPress Plugin

WordPress Plugin สำหรับเชื่อมต่อกับ Middleware API พร้อมใช้งานอยู่ในโฟลเดอร์ `wordpress-plugin/`

### ติดตั้ง Plugin

```bash
# 1. บีบอัดโฟลเดอร์เป็น zip
cd wordpress-plugin
zip -r sportmonks-middleware.zip .

# 2. ไปที่ WordPress Admin → Plugins → Add New → Upload Plugin
# 3. เลือกไฟล์ zip และติดตั้ง
```

### Shortcodes ที่ใช้ได้

```
[live_scores]                              # คะแนนสด
[league_fixtures league_id="8"]            # ตารางแข่งขัน
[league_standings league_id="8"]           # ตารางคะแนน
[team_fixtures team_id="1"]                # แมตช์ของทีม
```

ดูรายละเอียดเพิ่มเติมได้ที่ `wordpress-plugin/README.md`

## 🐳 Docker Deployment

### รันด้วย Docker Compose (แนะนำ)

```bash
# 1. คัดลอก .env.example เป็น .env และแก้ไขค่า
cp .env.example .env

# 2. Build และรัน containers
npm run docker:up

# 3. ดู logs
npm run docker:logs

# 4. หยุด containers
npm run docker:down
```

Docker Compose จะรัน 3 services:
- **redis**: Redis cache server
- **api**: Middleware API server
- **cron**: Pre-fetch scheduler

### Deploy บน Production

```bash
# Build image
docker-compose build

# รันแบบ production
docker-compose up -d

# ตรวจสอบสถานะ
docker-compose ps

# ดู logs
docker-compose logs -f api
docker-compose logs -f cron
```

## 📈 Performance Tips

1. **ตั้งค่า Redis Max Memory**
   ```bash
   redis-cli CONFIG SET maxmemory 256mb
   redis-cli CONFIG SET maxmemory-policy allkeys-lru
   ```

2. **เพิ่ม Worker Processes (Production)**
   ```bash
   npm install pm2 -g
   pm2 start server.js -i max
   ```

3. **Enable Nginx Reverse Proxy**
   ```nginx
   location /api/ {
       proxy_pass http://localhost:3000;
       proxy_cache_valid 200 1m;
   }
   ```

## 🐛 Troubleshooting

### Redis Connection Error
```bash
# ตรวจสอบว่า Redis รันอยู่หรือไม่
redis-cli ping
# ควรได้ PONG
```

### CORS Error
ตรวจสอบว่า URL ของ WordPress อยู่ใน `ALLOWED_ORIGINS` ใน `.env`

### Rate Limit Error
ลด `RATE_LIMIT_MAX_REQUESTS` หรือเพิ่ม `RATE_LIMIT_WINDOW_MS` ใน `.env`

## 📝 TODO / Roadmap

- [x] สร้าง Docker Compose สำหรับ Deploy
- [x] เพิ่ม Cron Job สำหรับ Pre-fetch ข้อมูล
- [x] สร้าง WordPress Plugin ตัวอย่าง
- [ ] สร้าง Admin Dashboard สำหรับดู Cache Stats
- [ ] เพิ่ม WebSocket สำหรับ Live Updates
- [ ] เพิ่ม Logging ด้วย Winston
- [ ] เพิ่ม Unit Tests
- [ ] สร้าง Kubernetes manifests

## 📄 License

ISC

## 👤 Author

Senior Developer Team

## 🔗 Links

- [SportMonks API Documentation](https://docs.sportmonks.com)
- [Redis Documentation](https://redis.io/documentation)
- [Express.js](https://expressjs.com)
# sportmonks-middleware
