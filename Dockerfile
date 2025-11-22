# ใช้ Node.js 20 LTS
FROM node:20-alpine

# ตั้งค่า working directory
WORKDIR /app

# คัดลอก package files
COPY package*.json ./

# ติดตั้ง dependencies (production only)
RUN npm ci --only=production

# คัดลอกโค้ดทั้งหมด
COPY . .

# สร้าง non-root user เพื่อความปลอดภัย
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nodejs -u 1001 && \
    chown -R nodejs:nodejs /app

# เปลี่ยนเป็น non-root user
USER nodejs

# Expose port
EXPOSE 3000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD node -e "require('http').get('http://localhost:3000/api/health', (r) => {process.exit(r.statusCode === 200 ? 0 : 1)})"

# Start application
CMD ["node", "server.js"]
