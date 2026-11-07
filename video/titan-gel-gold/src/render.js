// usage: node render.js stills t1 t2 ... | node render.js frames startFrame endFrame outDir
const { chromium } = require('playwright');
const fs=require('fs');
(async()=>{
  const [mode,...a]=process.argv.slice(2);
  const port=process.env.PORT||8765;
  const b=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader','--ignore-gpu-blocklist','--force-color-profile=srgb']});
  const p=await b.newPage({viewport:{width:1080,height:1920},deviceScaleFactor:1});
  p.on('console',m=>{if(m.type()==='error')console.log('CONSOLE',m.text())});p.on('pageerror',e=>console.log('PAGEERR',e.message));
  await p.goto(`http://127.0.0.1:${port}/index.html`);
  await p.waitForFunction('window.ready===true',null,{timeout:120000});
  const shot=async(t,file,q=92)=>{await p.evaluate(async t=>{await window.seek(t);await new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r)))},t);
    await p.screenshot({path:file,type:'jpeg',quality:q,clip:{x:0,y:0,width:1080,height:1920}})};
  if(mode==='stills'){fs.mkdirSync('probe',{recursive:true});for(const t of a){await shot(+t,`probe/s_${(+t).toFixed(2)}.jpg`,85)}}
  else{const [s,e,dir]=a;fs.mkdirSync(dir,{recursive:true});const t0=Date.now();
    for(let f=+s;f<+e;f++){await shot(f/30,`${dir}/f${String(f).padStart(5,'0')}.jpg`,94);if(f%50==0)console.log(dir,f,((Date.now()-t0)/1000).toFixed(0)+'s')}}
  await b.close();
})();
