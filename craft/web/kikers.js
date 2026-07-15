/* Kiker's U-Pull-It — shared behavior (every page loads this) */
(function(){
  document.documentElement.classList.add('motion-ready');
  if(window.lucide){window.lucide.createIcons();}
  // sticky nav border on scroll
  var nav=document.getElementById('nav');
  if(nav){var onScroll=function(){nav.classList.toggle('scrolled',window.scrollY>40);};window.addEventListener('scroll',onScroll,{passive:true});onScroll();}
  // mobile drawer
  var drawer=document.getElementById('drawer'),scrim=document.getElementById('scrim'),menu=document.getElementById('menuBtn');
  function setNav(open){
    drawer.classList.toggle('open',open);
    scrim.classList.toggle('open',open);
    menu.setAttribute('aria-expanded',String(open));
  }
  function openNav(){setNav(true);}
  function closeNav(){setNav(false);}
  if(menu&&drawer&&scrim){
    menu.onclick=function(){setNav(!drawer.classList.contains('open'));};
    scrim.onclick=closeNav;
    drawer.querySelectorAll('a').forEach(function(a){a.addEventListener('click',closeNav);});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&drawer.classList.contains('open')){closeNav();menu.focus();}});
  }
  // hours: highlight today + open/closed label
  var now=new Date(),d=now.getDay(),mins=now.getHours()*60+now.getMinutes();
  var row=document.querySelector('#htable tr[data-d="'+d+'"]');if(row)row.classList.add('today');
  var weekday=d>=1&&d<=5,saturday=d===6;
  var open=(weekday&&mins>=540&&mins<990)||(saturday&&mins>=480&&mins<840);
  var todayHours=weekday?'9-4:30':(saturday?'8-2':null);
  var closeLabel=weekday?'4:30 PM':'2 PM';
  var openLabel=weekday?'9 AM':(saturday?'8 AM':'9 AM');
  var on=document.getElementById('openNow');if(on)on.textContent=open?'Open Today '+todayHours:(d===0?'Closed Sunday':'Closed - Opens '+openLabel);
  var st=document.getElementById('statusText');if(st)st.textContent=open?'Open now - until '+closeLabel+' today':(d===0?'Closed today (Sunday)':'Closed now - opens '+openLabel);
  // chip toggles (inventory filters)
  document.querySelectorAll('.chip[aria-pressed]').forEach(function(c){c.addEventListener('click',function(){c.setAttribute('aria-pressed',c.getAttribute('aria-pressed')==='true'?'false':'true');});});
  // restrained section reveals; content remains visible when JS is unavailable
  var revealTargets=document.querySelectorAll('.cms-page main > section');
  if('IntersectionObserver' in window&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    var revealObserver=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('is-visible');revealObserver.unobserve(entry.target);}});},{rootMargin:'0px 0px -8% 0px',threshold:.08});
    revealTargets.forEach(function(el){el.classList.add('reveal-section');revealObserver.observe(el);});
  }else{revealTargets.forEach(function(el){el.classList.add('is-visible');});}
  // toast
  var tHost=document.getElementById('toastHost');
  window.toast=function(msg){if(!tHost)return;var t=document.createElement('div');t.className='toast';t.innerHTML='<span class="ic">✓</span>'+msg;tHost.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateY(8px)';t.style.transition='opacity .2s,transform .2s';setTimeout(function(){t.remove();},220);},3400);};
  function formPayload(form){
    var payload={rows:[]},vehicleParts={};
    Array.prototype.forEach.call(form.elements,function(el){
      if(!el.value||el.type==='submit'||el.type==='hidden'||el.tagName==='BUTTON'||el.disabled)return;
      var selectLabel=el.tagName==='SELECT'&&el.options.length?el.options[0].textContent:'';
      var label=(el.labels&&el.labels[0]?el.labels[0].textContent:el.getAttribute('aria-label')||el.name||el.placeholder||selectLabel||('Field '+(payload.rows.length+1))).trim();
      var key=label.toLowerCase().replace(/[^a-z0-9]+/g,' ');
      var value=String(el.value).trim();
      payload.rows.push({label:label,value:value});
      if(key.indexOf('phone')!==-1)payload.phone=value;
      else if(key.indexOf('email')!==-1)payload.email=value;
      else if(key==='name'||key.indexOf('your name')!==-1||key.indexOf('first last')!==-1)payload.name=value;
      else if(key.indexOf('condition')!==-1)payload.condition=value;
      else if(key.indexOf('zip')!==-1)payload.zip=value;
      else if(key==='year'||key.indexOf('vehicle year')!==-1)vehicleParts.year=value;
      else if(key==='make'||key.indexOf('vehicle make')!==-1)vehicleParts.make=value;
      else if(key==='model'||key.indexOf('vehicle model')!==-1)vehicleParts.model=value;
      else if(key.indexOf('year make')!==-1||key.indexOf('vehicle')!==-1)payload.vehicle=value;
      else if(key.indexOf('part')!==-1||key.indexOf('looking for')!==-1||key.indexOf('help with')!==-1)payload.subject=value;
      else if(key.indexOf('message')!==-1||key.indexOf('details')!==-1)payload.message=value;
    });
    if(!payload.vehicle){payload.vehicle=[vehicleParts.year,vehicleParts.make,vehicleParts.model].filter(Boolean).join(' ');}
    return payload;
  }
  async function submitForm(e,type){
    e.preventDefault();
    var form=e.target,button=form.querySelector('button[type="submit"]'),original=button?button.innerHTML:'';
    if(button){button.disabled=true;button.setAttribute('aria-busy','true');button.textContent='Sending...';}
    try{
      var sessionResponse=await fetch('/actions/users/session-info',{headers:{Accept:'application/json'}});
      var session=await sessionResponse.json();
      var data=new FormData();
      data.append(session.csrfTokenName,session.csrfTokenValue);
      data.append('submissionType',type);
      data.append('submissionSource',window.location.pathname);
      data.append('submissionPayload',JSON.stringify(formPayload(form)));
      data.append('website','');
      var response=await fetch('/actions/kikers/submissions/save',{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:data});
      var result=await response.json();
      if(!response.ok)throw new Error(result.message||'We could not send your request.');
      window.location.href=result.redirect||'/thank-you';
    }catch(error){
      toast(error.message||'We could not send your request. Please call the yard.');
      if(button){button.disabled=false;button.removeAttribute('aria-busy');button.innerHTML=original;}
    }
    return false;
  }
  window.submitOffer=function(e){return submitForm(e,'vehicle');};
  window.submitMsg=function(e){return submitForm(e,'message');};
})();
