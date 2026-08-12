const fs = require('fs');
const [schemaPath, instancePath] = process.argv.slice(2);
if (!schemaPath || !instancePath) process.exit(2);
const schema = JSON.parse(fs.readFileSync(schemaPath, 'utf8').replace(/^\uFEFF/, ''));
const value = JSON.parse(fs.readFileSync(instancePath, 'utf8').replace(/^\uFEFF/, ''));
const errors = [];
const supported = new Set(['$schema','$id','title','description','type','required','properties','additionalProperties','const','enum','pattern','minLength','maxLength','items','minItems','maxItems','uniqueItems','anyOf','allOf','oneOf','if','then','else']);
const typeOf = v => v === null ? 'null' : Array.isArray(v) ? 'array' : typeof v;
function validateDialect(s, p) {
  if (!s || typeof s !== 'object' || Array.isArray(s)) return;
  for (const key of Object.keys(s)) {
    if (!supported.has(key)) errors.push(`${p}:${key}:unsupportedKeyword`);
  }
  if (s.properties) for (const [name, child] of Object.entries(s.properties)) validateDialect(child, `${p}.properties.${name}`);
  if (s.items && typeof s.items === 'object') validateDialect(s.items, `${p}.items`);
  for (const key of ['anyOf','allOf','oneOf']) if (Array.isArray(s[key])) s[key].forEach((x,i) => validateDialect(x, `${p}.${key}[${i}]`));
  for (const key of ['if','then','else','additionalProperties']) if (s[key] && typeof s[key] === 'object') validateDialect(s[key], `${p}.${key}`);
}
function trial(s, v, p) { const before=errors.length; check(s,v,p); const ok=errors.length===before; errors.splice(before); return ok; }
function check(s,v,p) {
  if (!s || typeof s !== 'object') return;
  const types=Array.isArray(s.type)?s.type:[s.type].filter(Boolean);
  if(types.length&&!types.includes(typeOf(v))){errors.push(`${p}:type`);return;}
  if('const' in s&&JSON.stringify(v)!==JSON.stringify(s.const))errors.push(`${p}:const`);
  if(s.enum&&!s.enum.some(x=>JSON.stringify(x)===JSON.stringify(v)))errors.push(`${p}:enum`);
  if(typeof v==='string'){
    if(s.minLength!==undefined&&v.length<s.minLength)errors.push(`${p}:minLength`);
    if(s.maxLength!==undefined&&v.length>s.maxLength)errors.push(`${p}:maxLength`);
    if(s.pattern&&!(new RegExp(s.pattern).test(v)))errors.push(`${p}:pattern`);
  }
  if(Array.isArray(v)){
    if(s.minItems!==undefined&&v.length<s.minItems)errors.push(`${p}:minItems`);
    if(s.maxItems!==undefined&&v.length>s.maxItems)errors.push(`${p}:maxItems`);
    if(s.uniqueItems&&new Set(v.map(JSON.stringify)).size!==v.length)errors.push(`${p}:uniqueItems`);
    if(s.items)v.forEach((x,i)=>check(s.items,x,`${p}[${i}]`));
  }
  if(v&&typeOf(v)==='object'){
    (s.required||[]).forEach(k=>{if(!Object.prototype.hasOwnProperty.call(v,k))errors.push(`${p}.${k}:required`)});
    if(s.properties)Object.keys(v).forEach(k=>{if(s.properties[k])check(s.properties[k],v[k],`${p}.${k}`);else if(s.additionalProperties===false)errors.push(`${p}.${k}:additionalProperty`);else if(s.additionalProperties&&typeof s.additionalProperties==='object')check(s.additionalProperties,v[k],`${p}.${k}`)});
  }
  if(s.anyOf&&!s.anyOf.some(x=>trial(x,v,p)))errors.push(`${p}:anyOf`);
  if(s.oneOf){const matches=s.oneOf.filter(x=>trial(x,v,p)).length;if(matches!==1)errors.push(`${p}:oneOf`);}
  if(s.allOf)s.allOf.forEach(x=>check(x,v,p));
  if(s.if){const matched=trial(s.if,v,p);if(matched&&s.then)check(s.then,v,p);if(!matched&&s.else)check(s.else,v,p);}
}
validateDialect(schema,'$schema');
if(!errors.length)check(schema,value,'$');
const result={schema_version:1,dialect:'mdf-json-schema-restricted-2020-12-v1',result:errors.length?'failed':'passed',schema:schemaPath,instance:instancePath,errors};
process.stdout.write(JSON.stringify(result));process.exit(errors.length?1:0);
