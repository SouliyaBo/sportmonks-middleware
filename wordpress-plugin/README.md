# SportMonks Middleware - WordPress Plugin

WordPress Plugin สำหรับเชื่อมต่อกับ SportMonks Middleware API

## การติดตั้ง

### วิธีที่ 1: ติดตั้งผ่าน WordPress Admin

1. บีบอัดโฟลเดอร์ `wordpress-plugin` เป็นไฟล์ `.zip`
2. ไปที่ WordPress Admin → Plugins → Add New → Upload Plugin
3. เลือกไฟล์ `.zip` และกด Install Now
4. กด Activate Plugin

### วิธีที่ 2: ติดตั้งแบบ Manual

1. คัดลอกโฟลเดอร์ `wordpress-plugin` ไปที่ `wp-content/plugins/`
2. เปลี่ยนชื่อเป็น `sportmonks-middleware`
3. ไปที่ WordPress Admin → Plugins
4. เปิดใช้งาน "SportMonks Middleware Connector"

## การตั้งค่า

1. ไปที่ Settings → SportMonks API
2. ใส่ API Base URL (เช่น `http://localhost:3000/api` หรือ `https://api.yourdomain.com/api`)
3. ตั้งค่า Cache Duration (แนะนำ 300 วินาที)
4. Save Changes

## การใช้งาน Shortcodes

### 1. Live Scores
```
[live_scores]
```

### 2. League Fixtures
```
[league_fixtures league_id="8"]
```
- `league_id` = ID ของลีก (8 = Premier League, 564 = La Liga)
- `season` = ปี (optional)

### 3. League Standings
```
[league_standings league_id="8"]
```

### 4. Team Fixtures
```
[team_fixtures team_id="1"]
```

## ตัวอย่างการใช้งาน

### ในหน้า Post/Page
```
[live_scores]

[league_standings league_id="8"]

[league_fixtures league_id="564" season="2024"]
```

### ใน PHP Template
```php
<?php echo do_shortcode('[live_scores]'); ?>
```

## โครงสร้างไฟล์

```
wordpress-plugin/
├── sportmonks-middleware.php    # Main plugin file
├── templates/
│   ├── live-scores.php          # Live scores template
│   ├── fixtures.php             # Fixtures template
│   └── standings.php            # Standings template
├── assets/
│   ├── css/
│   │   └── style.css            # Plugin styles
│   └── js/
│       └── script.js            # Plugin scripts
└── README.md
```

## ฟีเจอร์

- ✅ แสดงคะแนนสด (Live Scores)
- ✅ แสดงตารางแข่งขัน (Fixtures)
- ✅ แสดงตารางคะแนน (Standings)
- ✅ รองรับ Caching ใน WordPress
- ✅ Responsive Design
- ✅ ใช้งานง่ายด้วย Shortcodes

## League IDs ยอดนิยม

- **8** - Premier League (England)
- **564** - La Liga (Spain)
- **384** - Serie A (Italy)
- **82** - Bundesliga (Germany)
- **301** - Ligue 1 (France)
- **2** - UEFA Champions League
- **5** - UEFA Europa League

## Support

หากมีปัญหาหรือข้อสงสัย กรุณาติดต่อผู้พัฒนา
