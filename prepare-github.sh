#!/bin/bash

# สคริปต์เตรียม project สำหรับ push ขึ้น GitHub

echo "🚀 เตรียม Project สำหรับ GitHub..."

# 1. ติดตั้ง dependencies
echo "📦 กำลังติดตั้ง dependencies..."
npm install

# 2. ตรวจสอบว่ามี Git repo หรือยัง
if [ ! -d ".git" ]; then
    echo "🔧 สร้าง Git repository..."
    git init
else
    echo "✅ Git repository มีอยู่แล้ว"
fi

# 3. สร้าง .gitignore ถ้ายังไม่มี
if [ ! -f ".gitignore" ]; then
    echo "📝 สร้าง .gitignore..."
    cat > .gitignore << 'EOF'
# Dependencies
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Environment Variables
.env
.env.local

# IDE
.vscode/
.idea/
.DS_Store

# Logs
logs/
*.log

# Build
dist/
build/

# Temporary
tmp/
temp/

# WordPress Plugin Zip
wordpress-plugin/*.zip
EOF
fi

# 4. Add และ commit
echo "📝 Adding files..."
git add .

echo "💾 Creating commit..."
git commit -m "Initial commit: SportMonks Middleware with WordPress Plugin" || echo "⚠️  No changes to commit"

echo ""
echo "✅ เสร็จสิ้น! ขั้นตอนถัดไป:"
echo ""
echo "1️⃣  สร้าง Repository ใหม่บน GitHub:"
echo "   👉 https://github.com/new"
echo ""
echo "2️⃣  เชื่อมต่อกับ GitHub:"
echo "   git remote add origin https://github.com/YOUR_USERNAME/sportmonks-middleware.git"
echo ""
echo "3️⃣  Push code ขึ้น GitHub:"
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""
echo "4️⃣  Deploy บน Railway หรือ Render:"
echo "   👉 อ่านวิธีใน QUICK-DEPLOY.md"
echo ""
