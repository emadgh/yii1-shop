// Keep the browser assets local and reproducible from the npm lockfile.
const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'..'),source=path.join(root,'node_modules/@majidh1/jalalidatepicker'),target=path.join(root,'public/assets/vendor/jalalidatepicker');
fs.mkdirSync(target,{recursive:true});
for(const name of ['jalalidatepicker.min.js','jalalidatepicker.min.css'])fs.copyFileSync(path.join(source,'dist',name),path.join(target,name));
fs.copyFileSync(path.join(source,'LICENSE'),path.join(target,'LICENSE'));
console.log('JalaliDatePicker browser assets and license copied.');
