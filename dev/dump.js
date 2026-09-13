const fs = require('fs');
let code = fs.readFileSync('projects-data.js', 'utf8');

// Replace const with var so eval exposes it globally
code = code.replace(/const RLABZ_PROJECTS =/, 'var RLABZ_PROJECTS =');
eval(code);

fs.writeFileSync('projects_dump.json', JSON.stringify(RLABZ_PROJECTS, null, 2));
console.log('Dumped to projects_dump.json');
