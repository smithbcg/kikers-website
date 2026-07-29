import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const source = "/Users/mgsbrasil/Desktop/kikers-yard-map-web (3).html";
const repoRoot = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const destination = path.join(repoRoot, 'craft/web/assets/maps/kikers-yard-map.svg');
const bundle = fs.readFileSync(source, 'utf8');
const templateMatch = bundle.match(/<script type="__bundler\/template">\s*("(?:\\.|[^"\\])*")\s*<\/script>/s);

if (!templateMatch) {
  throw new Error('Unable to read the yard map bundle template.');
}

const template = JSON.parse(templateMatch[1]);
const start = template.search(/<svg\b[^>]*data-yard-map[^>]*>/);
let svg = null;

if (start !== -1) {
  const tags = template.slice(start).matchAll(/<\/?svg\b[^>]*>/g);
  let depth = 0;
  let end = null;

  for (const match of tags) {
    depth += match[0].startsWith('</') ? -1 : 1;
    if (depth === 0) {
      end = start + match.index + match[0].length;
      break;
    }
  }

  if (end !== null) {
    svg = template.slice(start, end);
  }
}

if (!svg) {
  throw new Error('The branded yard map SVG was not found in the bundle.');
}

svg = svg
  .replace(/\bsc-camel-view-box=/g, 'viewBox=')
  .replace(/\bsc-camel-pattern-transform=/g, 'patternTransform=')
  .replace(/\bsc-camel-pattern-units=/g, 'patternUnits=')
  .replace(/\bsc-camel-ref-x=/g, 'refX=')
  .replace(/\bsc-camel-ref-y=/g, 'refY=')
  .replace(/\bsc-camel-marker-width=/g, 'markerWidth=')
  .replace(/\bsc-camel-marker-height=/g, 'markerHeight=')
  .replace(/<sc-if[^>]*>|<\/sc-if>/g, '')
  .replace(/\s+hint-placeholder-val="[^"]*"/g, '')
  .replace(/\{\{\s*[^}]+\s*\}\}/g, 'true')
  .replace(/<svg\b/, '<svg xmlns="http://www.w3.org/2000/svg"')
  .replace(/[ \t]+$/gm, '');

fs.mkdirSync(path.dirname(destination), {recursive: true});
fs.writeFileSync(destination, `${svg}\n`);
console.log(`Extracted branded yard map to ${destination}`);

const logoDirectory = path.join(repoRoot, 'craft/web/assets/logos');
fs.mkdirSync(logoDirectory, {recursive: true});
fs.copyFileSync(
  "/Users/mgsbrasil/Desktop/Kiker's Horizontal White Logo Final.svg",
  path.join(logoDirectory, 'kikers-horizontal-white.svg'),
);
fs.copyFileSync(
  "/Users/mgsbrasil/Desktop/Kiker's Horizontal Black Final Logo-04.svg",
  path.join(logoDirectory, 'kikers-horizontal-black.svg'),
);
console.log(`Copied supplied logo variants to ${logoDirectory}`);
