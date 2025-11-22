# DEPLOYMENT.md - คู่มือการ Deploy

## 🚀 วิธีการ Deploy Production

### 1. Deploy ด้วย Docker Compose (แนะนำ)

#### ขั้นตอนการเตรียมความพร้อม

```bash
# 1. Clone repository หรือ upload โปรเจกต์ไปยัง Server
git clone <your-repo>
cd sportmonks-middleware

# 2. สร้างและแก้ไขไฟล์ .env
cp .env.example .env
nano .env
```

#### แก้ไขไฟล์ .env สำหรับ Production

```env
NODE_ENV=production
PORT=3000

# SportMonks API - ใส่ API Key จริง
SPORTMONKS_API_KEY=your_actual_production_api_key
SPORTMONKS_BASE_URL=https://api.sportmonks.com/v3/football

# Redis (ใช้ค่า default สำหรับ Docker)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Cache TTL
CACHE_TTL_LIVESCORES=60
CACHE_TTL_FIXTURES=3600
CACHE_TTL_STANDINGS=86400
CACHE_TTL_TEAMS=604800

# CORS - ใส่ URL WordPress จริง
ALLOWED_ORIGINS=https://yourwordpress.com,https://www.yourwordpress.com

# Rate Limiting
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100
```

#### รัน Docker Compose

```bash
# Build และรัน
docker-compose up -d

# ตรวจสอบสถานะ
docker-compose ps

# ดู logs
docker-compose logs -f api
docker-compose logs -f cron

# Health check
curl http://localhost:3000/api/health
```

### 2. Deploy แบบ Manual (โดยไม่ใช้ Docker)

#### ติดตั้ง Dependencies

```bash
# 1. ติดตั้ง Node.js 20+
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 2. ติดตั้ง Redis
sudo apt update
sudo apt install redis-server
sudo systemctl start redis
sudo systemctl enable redis

# 3. ติดตั้ง PM2 (Process Manager)
sudo npm install -g pm2
```

#### รัน Application

```bash
# 1. ติดตั้ง dependencies
npm install --production

# 2. รัน API Server ด้วย PM2
pm2 start server.js --name sportmonks-api

# 3. รัน Cron Service
pm2 start src/services/cronService.js --name sportmonks-cron

# 4. บันทึก PM2 config
pm2 save
pm2 startup

# 5. ตรวจสอบสถานะ
pm2 status
pm2 logs sportmonks-api
```

### 3. Deploy ด้วย Nginx Reverse Proxy

#### ติดตั้ง Nginx

```bash
sudo apt update
sudo apt install nginx
```

#### สร้างไฟล์ Nginx Config

```bash
sudo nano /etc/nginx/sites-available/sportmonks-api
```

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        
        # Cache static responses
        proxy_cache_valid 200 1m;
    }
}
```

#### เปิดใช้งาน Config

```bash
sudo ln -s /etc/nginx/sites-available/sportmonks-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### ติดตั้ง SSL ด้วย Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.yourdomain.com
```

### 4. Deploy บน VPS (DigitalOcean, Linode, AWS EC2)

#### สร้าง Droplet/Instance

1. เลือก Ubuntu 22.04 LTS
2. ขนาดแนะนำ: 2GB RAM, 1 vCPU
3. เปิด Port: 22, 80, 443

#### Setup Server

```bash
# 1. เชื่อมต่อ SSH
ssh root@your_server_ip

# 2. อัพเดทระบบ
apt update && apt upgrade -y

# 3. สร้าง user ใหม่
adduser deploy
usermod -aG sudo deploy
su - deploy

# 4. ติดตั้ง Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
sudo usermod -aG docker $USER

# ติดตั้ง Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# 5. Clone project
git clone <your-repo>
cd sportmonks-middleware

# 6. Setup .env และรัน
cp .env.example .env
nano .env
docker-compose up -d
```

### 5. ติดตั้ง WordPress Plugin

```bash
# 1. บีบอัด plugin folder
cd wordpress-plugin
zip -r sportmonks-middleware.zip *

# 2. Upload ไปยัง WordPress
# ไปที่ WordPress Admin → Plugins → Add New → Upload
# หรือใช้ FTP upload ไปที่ wp-content/plugins/

# 3. เปิดใช้งาน Plugin
# ไปที่ Plugins → Activate "SportMonks Middleware Connector"

# 4. ตั้งค่า Plugin
# ไปที่ Settings → SportMonks API
# ใส่ API Base URL: https://api.yourdomain.com/api
```

## 🔒 Security Checklist

- [ ] แก้ไข `.env` ใส่ค่าจริงทั้งหมด
- [ ] ตั้งค่า `ALLOWED_ORIGINS` ให้ถูกต้อง
- [ ] ใช้ HTTPS สำหรับ Production (SSL Certificate)
- [ ] ตั้งค่า Firewall อนุญาตเฉพาะ Port ที่จำเป็น
- [ ] เปิดใช้งาน Rate Limiting
- [ ] Backup Redis data เป็นประจำ
- [ ] ตั้งค่า Redis password (ถ้าจำเป็น)
- [ ] ใช้ `.dockerignore` และ `.gitignore` ถูกต้อง

## 📊 Monitoring

### ดูสถานะ Docker Containers

```bash
docker-compose ps
docker-compose logs -f
docker stats
```

### ดู PM2 Status

```bash
pm2 status
pm2 logs
pm2 monit
```

### ตรวจสอบ Redis

```bash
redis-cli ping
redis-cli INFO
redis-cli DBSIZE
```

## 🔄 การอัพเดท

```bash
# Pull code ใหม่
git pull origin main

# Rebuild Docker images
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# หรือใช้ PM2
pm2 restart all
```

## 🐛 Troubleshooting

### Container ไม่สามารถเชื่อมต่อ Redis

```bash
# ตรวจสอบว่า Redis container รันอยู่
docker-compose ps redis

# Restart Redis
docker-compose restart redis
```

### API ตอบช้า

```bash
# ตรวจสอบ Redis memory
redis-cli INFO memory

# เคลียร์ Cache
redis-cli FLUSHALL
```

### WordPress ไม่สามารถเชื่อมต่อ API

1. ตรวจสอบ CORS settings ใน `.env`
2. ตรวจสอบว่า API URL ถูกต้อง
3. ลองเรียก API ด้วย cURL

```bash
curl https://api.yourdomain.com/api/health
```

## 📱 Contact & Support

หากพบปัญหาในการ Deploy ติดต่อ: your-email@example.com
