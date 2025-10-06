// Create static folders for clean routes so Apache can serve them without rewrites
// Duplicates index.html into build/<route>/index.html for a whitelist of routes

const fs = require('fs');
const path = require('path');

const buildDir = path.resolve(__dirname, '..', 'build');
const routes = ['landing', 'userpreferences'];

function ensureDir(p) {
  if (!fs.existsSync(p)) fs.mkdirSync(p, { recursive: true });
}

function copyIndexTo(route) {
  const src = path.join(buildDir, 'index.html');
  const destDir = path.join(buildDir, route);
  const dest = path.join(destDir, 'index.html');
  ensureDir(destDir);
  fs.copyFileSync(src, dest);
  // Also write a tiny redirect file for trailing slash variations if needed
}

function main() {
  if (!fs.existsSync(buildDir)) {
    console.error('Build folder not found:', buildDir);
    process.exit(1);
  }
  routes.forEach(copyIndexTo);
  console.log('Postbuild: created route folders:', routes.join(', '));
}

main();
