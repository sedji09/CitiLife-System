const fs = require('fs');
const path = require('path');

function walk(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(function(file) {
    file = path.join(dir, file);
    const stat = fs.statSync(file);
    if (stat && stat.isDirectory()) {
      results = results.concat(walk(file));
    } else {
      if(file.endsWith('.ts')) {
        results.push(file);
      }
    }
  });
  return results;
}

const files = walk('C:/Users/seigi/capstoneApp/src/app');
files.forEach(file => {
  let content = fs.readFileSync(file, 'utf8');
  if(content.includes('@ionic/angular/standalone')) {
    content = content.replace(/@ionic\/angular\/standalone/g, '@ionic/angular');
    fs.writeFileSync(file, content);
    console.log('Fixed ' + file);
  }
});
