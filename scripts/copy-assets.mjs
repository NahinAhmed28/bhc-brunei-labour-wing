import { cp, mkdir } from 'node:fs/promises';
await mkdir('public/assets/css',{recursive:true}); await mkdir('public/assets/js',{recursive:true}); await mkdir('public/assets/fonts',{recursive:true});
await cp('node_modules/bootstrap/dist/css/bootstrap.min.css','public/assets/css/bootstrap.min.css');
await cp('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js','public/assets/js/bootstrap.bundle.min.js');
await cp('node_modules/bootstrap-icons/font/bootstrap-icons.min.css','public/assets/css/bootstrap-icons.min.css');
await cp('node_modules/bootstrap-icons/font/fonts','public/assets/fonts',{recursive:true});
console.log('Bootstrap assets copied to public/assets.');
