# 🚀 คู่มือ Deploy Middleware สำหรับผู้เริ่มต้น

เมื่อ WordPress อยู่บน Hosting แต่ Middleware อยู่ที่ localhost คุณต้อง Deploy Middleware ขึ้น Server จริง

## ตัวเลือกที่ 1: Deploy บน Railway.app (ฟรี + ง่ายที่สุด) ⭐ แนะนำ

### ขั้นตอน:

1. **สมัครสมาชิก Railway**
   - ไปที่ https://railway.app
   - Sign up ด้วย GitHub

2. **สร้าง Project ใหม่**
   - คลิก "New Project"
   - เลือก "Deploy from GitHub repo"
   - เลือก repository ของคุณ (ต้อง push code ขึ้น GitHub ก่อน)

3. **เพิ่ม Redis Service**
   - คลิก "+ New"
   - เลือก "Database" → "Add Redis"

4. **ตั้งค่า Environment Variables**
   - คลิกที่ Service ของ API
   - ไปที่แท็บ "Variables"
   - เพิ่ม:
     ```
     NODE_ENV=production
     PORT=3000
     SPORTMONKS_API_KEY=your_key_here
     SPORTMONKS_BASE_URL=https://api.sportmonks.com/v3/football
     REDIS_HOST=${{Redis.REDIS_HOST}}
     REDIS_PORT=${{Redis.REDIS_PORT}}
     ALLOWED_ORIGINS=https://yourwordpress.com
     ```

5. **Deploy**
   - Railway จะ Deploy อัตโนมัติ
   - คุณจะได้ URL เช่น `https://sportmonks-api-production.up.railway.app`

6. **ตั้งค่าใน WordPress**
   - ไปที่ Settings → SportMonks API
   - ใส่ URL: `https://sportmonks-api-production.up.railway.app/api`

---

## ตัวเลือกที่ 2: Deploy บน Render.com (ฟรี)

### ขั้นตอน:

1. **สมัครสมาชิก Render**
   - ไปที่ https://render.com
   - Sign up ด้วย GitHub

2. **สร้าง Redis Instance**
   - คลิก "New +" → "Redis"
   - เลือก Free plan
   - บันทึก Internal Redis URL

3. **สร้าง Web Service**
   - คลิก "New +" → "Web Service"
   - เชื่อมต่อ GitHub repository
   - ตั้งค่า:
     - **Build Command**: `npm install`
     - **Start Command**: `node server.js`

4. **เพิ่ม Environment Variables**
   ```
   NODE_ENV=production
   SPORTMONKS_API_KEY=your_key_here
   SPORTMONKS_BASE_URL=https://api.sportmonks.com/v3/football
   REDIS_HOST=<your-redis-host>
   REDIS_PORT=6379
   ALLOWED_ORIGINS=https://yourwordpress.com
   ```

5. **Deploy**
   - คลิก "Create Web Service"
   - รอ deploy เสร็จ (ประมาณ 2-3 นาที)

---

## ตัวเลือกที่ 3: Deploy บน VPS (DigitalOcean, Linode)

### ราคา: $5-10/เดือน

### ขั้นตอนย่อ:

```bash
# 1. SSH เข้า VPS
ssh root@your_server_ip

# 2. ติดตั้ง Docker
curl -fsSL https://get.docker.com | sh

# 3. Upload project
# ใช้ Git clone หรือ FTP upload

# 4. Setup .env
cp .env.example .env
nano .env
# แก้ไขค่าตามจริง

# 5. รัน Docker Compose
docker-compose up -d

# 6. Setup Nginx + SSL
sudo apt install nginx certbot python3-certbot-nginx
sudo certbot --nginx -d api.yourdomain.com
```

---

## ตัวเลือกที่ 4: ใช้ ngrok สำหรับทดสอบชั่วคราว (ไม่แนะนำสำหรับ Production)

### เหมาะสำหรับ: ทดสอบเท่านั้น

```bash
# 1. ติดตั้ง ngrok
brew install ngrok  # macOS
# หรือดาวน์โหลดจาก https://ngrok.com

# 2. รัน Middleware
npm start

# 3. สร้าง tunnel (ใน terminal ใหม่)
ngrok http 3000

# 4. คัดลอก URL ที่ได้
# เช่น: https://abc123.ngrok.io

# 5. ใส่ใน WordPress Settings
# API URL: https://abc123.ngrok.io/api
```

**ข้อเสีย:**
- URL เปลี่ยนทุกครั้งที่รัน ngrok ใหม่ (แพลนฟรี)
- ไม่เสถียรสำหรับ Production
- ต้องเปิด localhost ไว้ตลอดเวลา

---

## 🎯 สรุปแนะนำ

| Platform | ราคา | ความยาก | เหมาะสำหรับ |
|----------|------|---------|-------------|
| **Railway.app** ⭐ | ฟรี | ⭐ ง่าย | Production ✅ |
| **Render.com** | ฟรี | ⭐⭐ ปานกลาง | Production ✅ |
| **VPS** | $5-10/เดือน | ⭐⭐⭐ ยาก | Production + Full Control ✅ |
| **ngrok** | ฟรี | ⭐ ง่าย | ทดสอบเท่านั้น ⚠️ |

---

## 📋 Checklist หลัง Deploy

- [ ] ทดสอบ API: `curl https://your-api-url.com/api/health`
- [ ] ตรวจสอบ CORS: ตั้งค่า `ALLOWED_ORIGINS` ให้ตรงกับ URL WordPress
- [ ] ตั้งค่า WordPress Plugin ให้ใช้ URL ใหม่
- [ ] ทดสอบ Shortcode ในหน้า WordPress
- [ ] ตรวจสอบ Cache ว่าทำงาน (ดูใน logs)

---

## 🆘 หากติดปัญหา

### CORS Error
```
Access to fetch at 'https://api.example.com' from origin 'https://wordpress.com' has been blocked by CORS
```

**แก้ไข:** เพิ่ม URL WordPress ใน `.env`
```env
ALLOWED_ORIGINS=https://yourwordpress.com,https://www.yourwordpress.com
```

### Connection Timeout
**สาเหตุ:** API Server ไม่ได้รัน หรือ URL ผิด

**แก้ไข:**
1. ตรวจสอบว่า API รันอยู่: `curl https://your-api.com/api/health`
2. ตรวจสอบ URL ใน WordPress Settings

### Rate Limit Error
**สาเหตุ:** เรียก API บ่อยเกินไป

**แก้ไข:** เพิ่มค่า Cache Duration ใน WordPress Settings (แนะนำ 300 วินาที)

---

## 📞 Contact

หากต้องการความช่วยเหลือเพิ่มเติม:
- Discord: https://discord.gg/sportmonks
- Email: support@example.com
