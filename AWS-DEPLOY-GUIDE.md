# 🚀 Deploy SportMonks Middleware บน AWS EC2

## 📋 สิ่งที่ต้องเตรียม

- ✅ บัญชี AWS (สมัครฟรีได้ที่ https://aws.amazon.com/free)
- ✅ Code อยู่บน GitHub แล้ว: `SouliyaBo/sportmonks-middleware`
- ✅ SportMonks API Key: `D50P021LVCG6bLcZkEL0zYyJNjqgvMhJ3ibiS6Z4u8fqURFkYBeJFkrCR6ow`

---

## 💰 ค่าใช้จ่าย

- **AWS Free Tier**: ฟรี 12 เดือนแรก (t2.micro หรือ t3.micro)
- **หลัง Free Tier หมด**: ประมาณ $8-10/เดือน

---

## 🎯 ขั้นตอนการ Deploy

### ขั้นตอนที่ 1: สร้าง EC2 Instance

1. **Login เข้า AWS Console**
   - ไปที่ https://console.aws.amazon.com
   - Login ด้วย account ของคุณ

2. **ไปที่ EC2 Dashboard**
   - ค้นหา "EC2" ในช่องค้นหา
   - คลิกที่ "EC2"

3. **Launch Instance**
   - คลิกปุ่ม **"Launch Instance"** สีส้ม
   
4. **ตั้งค่า Instance**

   **Name and tags:**
   ```
   Name: sportmonks-middleware
   ```

   **Application and OS Images (AMI):**
   - เลือก **Ubuntu Server 22.04 LTS** (Free tier eligible)

   **Instance type:**
   - เลือก **t2.micro** หรือ **t3.micro** (Free tier eligible)
   - 1 vCPU, 1 GB RAM

   **Key pair (login):**
   - คลิก **"Create new key pair"**
   - Key pair name: `sportmonks-key`
   - Key pair type: **RSA**
   - Private key format: **.pem** (สำหรับ Mac/Linux)
   - คลิก **"Create key pair"**
   - ไฟล์ `sportmonks-key.pem` จะถูกดาวน์โหลดมา **เก็บไว้ให้ดี!**

   **Network settings:**
   - คลิก **"Edit"**
   - Auto-assign public IP: **Enable**
   - Firewall (security groups): **Create security group**
   - Security group name: `sportmonks-sg`
   - คลิก **"Add security group rule"** เพิ่มกฎเหล่านี้:
     ```
     Type: SSH          | Port: 22   | Source: My IP
     Type: HTTP         | Port: 80   | Source: Anywhere (0.0.0.0/0)
     Type: HTTPS        | Port: 443  | Source: Anywhere (0.0.0.0/0)
     Type: Custom TCP   | Port: 3000 | Source: Anywhere (0.0.0.0/0)
     ```

   **Configure storage:**
   - 8 GB gp3 (default) - Free tier eligible

5. **Launch Instance**
   - คลิก **"Launch instance"**
   - รอสักครู่จน Instance สถานะเป็น **"Running"**

6. **บันทึก Public IP**
   - ในรายการ Instances เลือก instance ที่สร้าง
   - คัดลอก **Public IPv4 address** (เช่น `3.25.123.456`)

---

### ขั้นตอนที่ 2: เตรียม Key File

เปิด Terminal บน Mac แล้วรันคำสั่งเหล่านี้:

```bash
# ย้ายไฟล์ key ไปไว้ในโฟลเดอร์ .ssh
cd ~/Downloads
mkdir -p ~/.ssh
mv sportmonks-key.pem ~/.ssh/

# ตั้งค่า permission
chmod 400 ~/.ssh/sportmonks-key.pem
```

---

### ขั้นตอนที่ 3: เชื่อมต่อกับ EC2

```bash
# SSH เข้า EC2 (แทน YOUR_IP ด้วย Public IP จริง)
ssh -i ~/.ssh/sportmonks-key.pem ubuntu@YOUR_IP

# ตัวอย่าง:
# ssh -i ~/.ssh/sportmonks-key.pem ubuntu@3.25.123.456
```

ถ้าถามว่า "Are you sure you want to continue connecting?" พิมพ์ `yes` แล้ว Enter

---

### ขั้นตอนที่ 4: ติดตั้ง Software บน EC2

พิมพ์คำสั่งเหล่านี้ทีละบรรทัด:

```bash
# 1. อัปเดตระบบ
sudo apt update && sudo apt upgrade -y

# 2. ติดตั้ง Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# ตรวจสอบเวอร์ชั่น
node -v   # ควรได้ v20.x.x
npm -v    # ควรได้ 10.x.x

# 3. ติดตั้ง Git
sudo apt install -y git

# 4. ติดตั้ง Docker & Docker Compose
sudo apt install -y docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker ubuntu

# รีสตาร์ท session (ออกแล้วเข้าใหม่)
exit

# เข้าใหม่อีกครั้ง
ssh -i ~/.ssh/sportmonks-key.pem ubuntu@YOUR_IP
```

---

### ขั้นตอนที่ 5: Clone Project

```bash
# Clone repository
git clone https://github.com/SouliyaBo/sportmonks-middleware.git
cd sportmonks-middleware

# สร้างไฟล์ .env
nano .env
```

ใส่ข้อมูลเหล่านี้ใน .env (กด Ctrl+O เพื่อ Save, Ctrl+X เพื่อออก):

```env
# Server Configuration
PORT=3000
NODE_ENV=production

# SportMonks API
SPORTMONKS_API_KEY=D50P021LVCG6bLcZkEL0zYyJNjqgvMhJ3ibiS6Z4u8fqURFkYBeJFkrCR6ow
SPORTMONKS_BASE_URL=https://api.sportmonks.com/v3/football

# Redis Configuration (Docker จะใช้ค่านี้)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Cache TTL
CACHE_TTL_LIVESCORES=60
CACHE_TTL_FIXTURES=3600
CACHE_TTL_STANDINGS=86400
CACHE_TTL_TEAMS=604800

# CORS Configuration - ใส่ URL WordPress จริงของคุณ
ALLOWED_ORIGINS=https://yourwordpress.com,https://www.yourwordpress.com

# Rate Limiting
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100
```

**⚠️ สำคัญ:** แก้ `ALLOWED_ORIGINS` ให้เป็น URL WordPress จริงของคุณ!

---

### ขั้นตอนที่ 6: รัน Docker Compose

```bash
# Build และรัน containers
docker-compose up -d

# ตรวจสอบสถานะ
docker-compose ps

# ดู logs (กด Ctrl+C เพื่อออก)
docker-compose logs -f
```

คุณควรเห็น:
```
sportmonks-api    | 🚀 Server กำลังทำงานที่ http://localhost:3000
sportmonks-redis  | Ready to accept connections
sportmonks-cron   | 🕒 Cron Service เริ่มทำงาน...
```

---

### ขั้นตอนที่ 7: ทดสอบ API

เปิด Browser บนเครื่องของคุณ ไปที่:

```
http://YOUR_EC2_IP:3000/api/health
```

ตัวอย่าง: `http://3.25.123.456:3000/api/health`

ถ้าเห็น:
```json
{
  "success": true,
  "message": "SportMonks Middleware API is running"
}
```

แสดงว่า **สำเร็จแล้ว!** 🎉

---

### ขั้นตอนที่ 8: ตั้งค่า Domain Name (Optional แต่แนะนำ)

#### วิธีที่ 1: ใช้ AWS Route 53

1. ซื้อ Domain หรือใช้ Domain ที่มีอยู่
2. ไปที่ Route 53 → Create Hosted Zone
3. เพิ่ม A Record ชี้ไปที่ Public IP ของ EC2

#### วิธีที่ 2: ใช้ Domain Provider ที่มีอยู่

ไปที่ DNS Settings ของ Domain Provider แล้วเพิ่ม:
```
Type: A Record
Name: api (หรือชื่อที่ต้องการ)
Value: YOUR_EC2_IP
TTL: 300
```

รอประมาณ 5-30 นาที แล้วเข้า `http://api.yourdomain.com:3000`

---

### ขั้นตอนที่ 9: ติดตั้ง SSL (HTTPS) - แนะนำ

```bash
# ติดตั้ง Nginx
sudo apt install -y nginx

# สร้าง config
sudo nano /etc/nginx/sites-available/sportmonks
```

ใส่ config นี้ (แทน `api.yourdomain.com` ด้วย domain จริง):

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
    }
}
```

เปิดใช้งาน:

```bash
# Link config
sudo ln -s /etc/nginx/sites-available/sportmonks /etc/nginx/sites-enabled/

# ทดสอบ config
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx

# ติดตั้ง SSL Certificate (Let's Encrypt - ฟรี)
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.yourdomain.com

# ตอบคำถาม:
# Email: ใส่ email ของคุณ
# Terms of Service: A (Agree)
# Share email: N (No)
# Redirect HTTP to HTTPS: 2 (Yes)
```

ตอนนี้คุณสามารถเข้า:
```
https://api.yourdomain.com/api/health
```

---

### ขั้นตอนที่ 10: ตั้งค่าใน WordPress

1. **ติดตั้ง Plugin**
   - ไปที่ WordPress Admin → Plugins → Add New → Upload
   - เลือกไฟล์ `sportmonks-middleware.zip`
   - Activate Plugin

2. **ตั้งค่า API URL**
   - Settings → SportMonks API
   - API Base URL:
     - ถ้ามี Domain + SSL: `https://api.yourdomain.com/api`
     - ถ้าไม่มี Domain: `http://YOUR_EC2_IP:3000/api`
   - Cache Duration: `300`
   - Save Changes

3. **ทดสอบ Shortcode**
   ```
   [live_scores]
   
   [league_standings league_id="8"]
   ```

---

## 🔧 คำสั่งที่ใช้บ่อย

```bash
# SSH เข้า EC2
ssh -i ~/.ssh/sportmonks-key.pem ubuntu@YOUR_IP

# ดู logs
docker-compose logs -f api
docker-compose logs -f cron

# Restart services
docker-compose restart

# หยุด services
docker-compose down

# เริ่ม services ใหม่
docker-compose up -d

# Pull code ใหม่จาก GitHub
git pull origin main
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# ตรวจสอบ disk space
df -h

# ตรวจสอบ memory
free -h

# ดู running containers
docker ps
```

---

## 🆘 แก้ปัญหาที่พบบ่อย

### ปัญหา: SSH connection timeout

**แก้:**
1. ตรวจสอบว่า Security Group เปิด Port 22 ให้ IP ของคุณ
2. ตรวจสอบว่า Instance สถานะเป็น "Running"
3. ลองใช้ Public IPv4 DNS แทน IP

---

### ปัญหา: Cannot connect to API from browser

**แก้:**
1. ตรวจสอบ Security Group เปิด Port 3000
2. ตรวจสอบว่า containers รันอยู่: `docker-compose ps`
3. ดู logs: `docker-compose logs api`

---

### ปัญหา: CORS Error ใน WordPress

**แก้:**
1. SSH เข้า EC2
2. แก้ไข .env: `nano .env`
3. เพิ่ม URL WordPress ใน `ALLOWED_ORIGINS`
4. Restart: `docker-compose restart`

---

## 💡 Tips

1. **Backup ไฟล์ .pem key ไว้ให้ดี** - หายแล้วเข้า EC2 ไม่ได้!
2. **ตั้ง Elastic IP** (ถ้าต้องการ IP ไม่เปลี่ยน):
   - EC2 → Elastic IPs → Allocate Elastic IP
   - Associate กับ Instance
3. **ตั้ง Auto Restart**:
   ```bash
   docker update --restart unless-stopped $(docker ps -q)
   ```

---

## 📊 ตรวจสอบค่าใช้จ่าย

- ไปที่ AWS Billing Dashboard
- ดูที่ "Free tier usage" และ "Cost Explorer"

---

## ✅ Checklist หลัง Deploy

- [ ] API Health Check ผ่าน: `http://YOUR_IP:3000/api/health`
- [ ] ทดสอบ Live Scores: `http://YOUR_IP:3000/api/livescores`
- [ ] WordPress Plugin ติดตั้งแล้ว
- [ ] ตั้งค่า API URL ใน WordPress
- [ ] Shortcode แสดงข้อมูลได้
- [ ] ไม่มี CORS error
- [ ] (Optional) ติดตั้ง SSL แล้ว
- [ ] Backup .pem key แล้ว

---

## 🎓 สรุป

คุณได้:
- ✅ EC2 Instance บน AWS
- ✅ Docker + Redis รันอยู่
- ✅ API รัน 24/7
- ✅ Cron Job ทำงานอัตโนมัติ
- ✅ WordPress เชื่อมต่อได้แล้ว

**Total Time:** 30-45 นาที

---

## 📞 ต้องการความช่วยเหลือ?

หากติดขั้นตอนไหน ถามได้เลยครับ! 😊
