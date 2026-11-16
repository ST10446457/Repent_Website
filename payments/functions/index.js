'use strict';
// REPENT PayFast hosted checkout. Sandbox ONLY. Keep merchant secrets in Secret Manager.
const express=require('express');
const crypto=require('node:crypto');
const dns=require('node:dns').promises;
const {onRequest}=require('firebase-functions/v2/https');
const {defineSecret}=require('firebase-functions/params');
const admin=require('firebase-admin');
admin.initializeApp();
const db=admin.firestore(), app=express();
const mid=defineSecret('PAYFAST_MERCHANT_ID');
const mkey=defineSecret('PAYFAST_MERCHANT_KEY');
const phrase=defineSecret('PAYFAST_PASSPHRASE');
app.disable('x-powered-by');app.set('trust proxy',1);
const pf='https://sandbox.payfast.co.za';
function base(){
  const s=process.env.PAYMENT_PUBLIC_URL||'';
  if(!/^https:\/\/[a-z0-9.-]+(?:\/[a-z0-9/_-]*)?$/i.test(s))throw Error('Invalid PAYMENT_PUBLIC_URL');
  return s.replace(/\/$/,'');
}
const encode=v=>encodeURIComponent(String(v)).replace(/%20/g,'+').replace(/[!'()*~]/g,c=>'%'+c.charCodeAt(0).toString(16).toUpperCase());
const pairs=o=>Object.entries(o).filter(([k,v])=>k!=='signature'&&v!==''&&v!==null&&v!==undefined).map(([k,v])=>k+'='+encode(v)).join('&');
const sign=o=>crypto.createHash('md5').update(pairs(o)+(phrase.value()?'&passphrase='+encode(phrase.value()):'')).digest('hex');
const match=(a,b)=>/^[0-9a-f]{32}$/i.test(a)&&/^[0-9a-f]{32}$/i.test(b)&&crypto.timingSafeEqual(Buffer.from(a.toLowerCase()),Buffer.from(b.toLowerCase()));
const html=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const cents=n=>Math.round(Number(n)*100);
const hash=s=>crypto.createHash('sha256').update(s).digest('hex');
async function user(req){
  const m=/^Bearer (.+)$/i.exec(req.get('authorization')||'');
  if(!m)throw Error('Authentication required');
  const decoded=await admin.auth().verifyIdToken(m[1],true);
  if(!decoded.email_verified)throw Error('Email not verified');
  return decoded;
}
app.post('/create',express.json({limit:'12kb'}),async(req,res)=>{
 try{
  const u=await user(req);
  const {items,address,city,postalCode,phone}=req.body||{};
  if(!Array.isArray(items)||!items.length||items.length>30||
      ![address,city,postalCode,phone].every(v=>typeof v==='string'&&v.trim().length>=2&&v.length<=180))
      return res.status(400).json({error:'Invalid cart or delivery information'});
  const counts=new Map();
  for(const item of items){
    const id=Number(item.productId), qty=Number(item.quantity);
    if(!Number.isSafeInteger(id)||id<1||!Number.isSafeInteger(qty)||qty<1||qty>20)
      return res.status(400).json({error:'Invalid product quantity'});
    counts.set(id,(counts.get(id)||0)+qty);
  }
  let subtotal=0;const lines=[];
  for(const [id,qty] of counts){
    if(qty>20)return res.status(400).json({error:'Quantity too large'});
    const s=await db.collection('products').doc(String(id)).get();
    if(!s.exists)return res.status(400).json({error:'Product does not exist'});
    const p=s.data(),price=cents(p.price);
    if(!Number.isSafeInteger(price)||price<500||!Number.isFinite(p.stock)||p.stock<qty)
      return res.status(400).json({error:'Product unavailable'});
    subtotal+=price*qty;
    lines.push({productId:id,productName:String(p.name),price:price/100,quantity:qty});
  }
  if(subtotal>100000000)return res.status(400).json({error:'Cart too large'});
  const ship=subtotal>=80000?0:9500,total=subtotal+ship;
  const orderRef=db.collection('orders').doc(),token=crypto.randomBytes(32).toString('hex');
  const sessionRef=db.collection('payment_sessions').doc(hash(token));
  const number=crypto.randomInt(100000000,999999999),batch=db.batch();
  batch.create(orderRef,{
    userId:u.uid,orderNumber:number,items:lines,subtotal:subtotal/100,shipping:ship/100,total:total/100,
    shippingAddress:address.trim(),city:city.trim(),postalCode:postalCode.trim(),phone:phone.trim(),
    paymentMethod:'payfast',paymentStatus:'pending',status:'pending',currency:'ZAR',
    createdAt:admin.firestore.FieldValue.serverTimestamp()
  });
  batch.create(sessionRef,{orderId:orderRef.id,userId:u.uid,expiresAt:Date.now()+900000});
  await batch.commit();
  return res.status(201).json({orderId:orderRef.id,orderNumber:number,checkoutUrl:base()+'/pay/'+token});
 }catch(e){console.error('Create checkout:',e.message);return res.status(401).json({error:'Checkout could not be created'});}
});
app.get('/pay/:token',async(req,res)=>{
 try{
  if(!/^[0-9a-f]{64}$/.test(req.params.token))return res.status(404).send('Invalid checkout');
  const s=(await db.collection('payment_sessions').doc(hash(req.params.token)).get()).data();
  if(!s||s.expiresAt<Date.now())return res.status(410).send('Checkout expired');
  const o=(await db.collection('orders').doc(s.orderId).get()).data();
  if(!o||o.userId!==s.userId||o.paymentStatus!=='pending')return res.status(409).send('Checkout unavailable');
  const data={
    merchant_id:mid.value(),merchant_key:mkey.value(),return_url:base()+'/return',
    cancel_url:base()+'/cancel',notify_url:base()+'/notify',
    email_address:(await admin.auth().getUser(s.userId)).email||'',
    m_payment_id:s.orderId,amount:Number(o.total).toFixed(2),item_name:'REPENT Order '+o.orderNumber
  };
  data.signature=sign(data);
  const fields=Object.entries(data).map(([k,v])=>'<input type="hidden" name="'+html(k)+'" value="'+html(v)+'">').join('');
  res.set('Cache-Control','no-store').set('Content-Security-Policy',"default-src 'none'; script-src 'unsafe-inline'; form-action https://sandbox.payfast.co.za");
  return res.status(200).type('html').send('<!doctype html><meta charset="utf-8"><h2>Connecting to PayFast sandbox</h2><form id="pf" action="'+pf+'/eng/process" method="post">'+fields+'<button>Continue</button></form><script>document.getElementById("pf").submit()</script>');
 }catch(e){console.error('PayFast redirect:',e.message);return res.status(500).send('Checkout unavailable');}
});
// PayFast publishes allowed ITN networks; check these plus its official domains.
// This check is deliberately fail-closed if proxy routing hides the true sender.
const net=require('node:net');
const ranges=[
 ['197.97.145.144',28],['41.74.179.192',27],
 ['102.216.36.0',28],['102.216.36.128',28],['144.126.193.139',32]
];
function ipNumber(ip){return ip.split('.').reduce((n,x)=>((n<<8)>>>0)+(Number(x)>>>0),0)>>>0;}
function inRange(ip,block,bits){
 if(net.isIP(ip)!==4)return false;
 const mask=(0xffffffff<<(32-bits))>>>0;
 return (ipNumber(ip)&mask)===(ipNumber(block)&mask);
}
async function sourceValid(req){
 const ip=String(req.ip||'').replace(/^::ffff:/,'');
 if(net.isIP(ip)!==4)return false;
 if(ranges.some(([address,bits])=>inRange(ip,address,bits)))return true;
 const hosts=['www.payfast.co.za','w1w.payfast.co.za','w2w.payfast.co.za','sandbox.payfast.co.za'];
 const results=await Promise.all(hosts.map(host=>dns.lookup(host,{all:true}).catch(()=>[])));
 return results.flat().some(x=>x.address===ip);
}
app.post('/notify',express.urlencoded({extended:false,limit:'12kb'}),async(req,res)=>{
 try{
  const d=req.body||{};
  if(!await sourceValid(req))return res.status(403).send('Untrusted source');
  if(!match(String(d.signature||''),sign(d)))return res.status(400).send('Bad signature');
  if(String(d.merchant_id)!==mid.value())return res.status(400).send('Wrong merchant');
  const oid=String(d.m_payment_id||'');
  if(!/^[a-zA-Z0-9]{10,40}$/.test(oid))return res.status(400).send('Wrong reference');
  const ref=db.collection('orders').doc(oid),snap=await ref.get();
  if(!snap.exists)return res.status(404).send('Unknown order');
  if(d.payment_status!=='COMPLETE'||cents(d.amount_gross)!==cents(snap.data().total))
    return res.status(400).send('Payment status or amount mismatch');
  if(!d.pf_payment_id)return res.status(400).send('Missing transaction id');
  const check=await fetch(pf+'/eng/query/validate',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:pairs(d),signal:AbortSignal.timeout(10000)
  });
  if(!check.ok||(await check.text()).trim()!=='VALID')return res.status(400).send('Provider did not validate');
  const txRef=db.collection('payfast_transactions').doc(String(d.pf_payment_id));
  await db.runTransaction(async tx=>{
    const [current,used]=await Promise.all([tx.get(ref),tx.get(txRef)]);
    if(used.exists&&used.data().orderId!==oid)throw Error('Transaction reused');
    if(current.data().paymentStatus==='paid')return;
    if(current.data().paymentStatus!=='pending'||used.exists)throw Error('Conflicting state');
    tx.create(txRef,{orderId:oid,createdAt:admin.firestore.FieldValue.serverTimestamp()});
    tx.update(ref,{paymentStatus:'paid',payfastPaymentId:String(d.pf_payment_id),paidAt:admin.firestore.FieldValue.serverTimestamp()});
  });
  return res.status(200).send('OK');
 }catch(e){console.error('ITN validation:',e.message);return res.status(503).send('Unable to verify');}
});
app.get('/status/:id',async(req,res)=>{
 try{
  const u=await user(req),s=await db.collection('orders').doc(req.params.id).get();
  if(!s.exists||s.data().userId!==u.uid)return res.status(404).json({error:'Not found'});
  return res.set('Cache-Control','no-store').json({orderId:s.id,paymentStatus:s.data().paymentStatus,status:s.data().status});
 }catch(e){return res.status(401).json({error:'Authentication required'});}
});
app.get('/return',(_req,res)=>res.type('html').send('<h2>Returned from PayFast</h2><p>Check your REPENT order status; payment is confirmed only by the server notification.</p>'));
app.get('/cancel',(_req,res)=>res.type('html').send('<h2>Payment cancelled</h2><p>Your payment has not been confirmed.</p>'));
exports.repentPayfast=onRequest({region:'europe-west1',secrets:[mid,mkey,phrase],invoker:'public'},app);
