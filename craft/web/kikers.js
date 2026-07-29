/* Kiker's U-Pull-It — shared behavior (every page loads this) */
(function(){
  document.documentElement.classList.add('motion-ready');
  var arioEmbedHosts=['kikersautoparts.com','www.kikersautoparts.com','kikersupullit.com','www.kikersupullit.com'];
  if(arioEmbedHosts.indexOf(window.location.hostname.toLowerCase())===-1){
    document.documentElement.classList.add('ario-embed-disabled');
  }
  // Normalize all legacy glyphs and Lucide placeholders to the approved
  // custom Kiker icon artwork. This keeps older CMS-authored sections on the
  // same icon system without making editors rebuild each block by hand.
  var iconNames={
    camera:'inspection','car-front':'cash-vehicle',check:'verified',
    'clipboard-check':'paperwork','clipboard-list':'paperwork',history:'hours',
    info:'verified','map-pin':'location',phone:'fast-response',recycle:'recycle',
    'search-check':'part','shield-check':'safety',truck:'towing',users:'teamwork',
    zap:'cash-offer','package-search':'warehouse','message-square-text':'connection',
    bell:'fast-response','badge-dollar-sign':'cash-offer'
  };
  var legacyGlyphs={
    '⚡':'cash-offer','🚚':'towing','🛡️':'safety','🤝':'teamwork','💵':'cash-vehicle',
    '🔧':'wrench','🏷️':'part','📦':'warehouse','📍':'location','📞':'fast-response',
    '✓':'verified','♻️':'recycle','♻':'recycle','⛽':'engine','⏱':'hours',
    '⚙':'gears','📅':'hours','✉️':'connection','💬':'connection','🧰':'tool-kit',
    '👟':'safety','⚠️':'safety','🎟️':'cash-offer','🔞':'safety','🚧':'safety',
    '🛞':'wheel','🌐':'connection','📋':'paperwork','🏁':'fast-work','🔑':'verified',
    '⌕':'part'
  };
  function applyKikerIcon(el,name){
    if(!name)return;
    el.removeAttribute('data-lucide');
    el.classList.add('kiker-icon','kiker-icon--'+name);
    el.setAttribute('aria-hidden','true');
    el.textContent='';
  }
  document.querySelectorAll('[data-lucide]').forEach(function(el){
    applyKikerIcon(el,iconNames[el.getAttribute('data-lucide')]||'part');
  });
  document.querySelectorAll('.ic,.c,.ck,.vc').forEach(function(el){
    applyKikerIcon(el,legacyGlyphs[el.textContent.trim()]);
  });
  // A few legacy templates placed glyphs directly in list items, badges, and
  // utility text. Replace those text-node occurrences as well so no emoji icon
  // survives outside the standard icon wrappers.
  var glyphKeys=Object.keys(legacyGlyphs).sort(function(a,b){return b.length-a.length;});
  var walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT,{
    acceptNode:function(node){
      var parent=node.parentElement;
      if(!parent||parent.closest('script,style,noscript,textarea'))return NodeFilter.FILTER_REJECT;
      return glyphKeys.some(function(glyph){return node.nodeValue.indexOf(glyph)!==-1;})
        ? NodeFilter.FILTER_ACCEPT
        : NodeFilter.FILTER_REJECT;
    }
  });
  var glyphTextNodes=[],glyphNode;
  while((glyphNode=walker.nextNode()))glyphTextNodes.push(glyphNode);
  glyphTextNodes.forEach(function(node){
    var remaining=node.nodeValue,fragment=document.createDocumentFragment();
    while(remaining){
      var match=null,index=-1;
      glyphKeys.forEach(function(glyph){
        var candidate=remaining.indexOf(glyph);
        if(candidate!==-1&&(index===-1||candidate<index)){match=glyph;index=candidate;}
      });
      if(index===-1){fragment.appendChild(document.createTextNode(remaining));break;}
      if(index>0)fragment.appendChild(document.createTextNode(remaining.slice(0,index)));
      var icon=document.createElement('span');
      icon.className='legacy-inline-icon kiker-icon kiker-icon--'+legacyGlyphs[match];
      icon.setAttribute('aria-hidden','true');
      fragment.appendChild(icon);
      remaining=remaining.slice(index+match.length);
    }
    node.parentNode.replaceChild(fragment,node);
  });
  // Keep the recurring conversion and visit actions visually consistent even
  // when an editor changes the button label or creates a new button in Craft.
  document.querySelectorAll('.btn').forEach(function(button){
    if(button.querySelector('.kiker-icon,.lucide'))return;
    var label=button.textContent.trim().toLowerCase(),iconName='';
    if(label.indexOf('call')!==-1)iconName='fast-response';
    else if(label.indexOf('cash offer')!==-1||label.indexOf('get an offer')!==-1||label.indexOf('online offer')!==-1)iconName='cash-offer';
    else if(label.indexOf('direction')!==-1||label.indexOf('visit')!==-1)iconName='location';
    if(!iconName)return;
    var icon=document.createElement('span');
    icon.className='kiker-icon kiker-icon--'+iconName;
    icon.setAttribute('aria-hidden','true');
    button.insertBefore(icon,button.firstChild);
  });
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
  // leadership profiles: hover/focus works in CSS; the button provides an explicit touch toggle
  document.querySelectorAll('.leader-card').forEach(function(card){
    var toggle=card.querySelector('.leader-card__toggle');
    if(!toggle)return;
    function setProfile(open){card.classList.toggle('is-open',open);toggle.setAttribute('aria-expanded',String(open));}
    toggle.addEventListener('click',function(){setProfile(!card.classList.contains('is-open'));});
    card.addEventListener('keydown',function(e){if(e.key==='Escape'){setProfile(false);toggle.focus();}});
  });
  // FAQ motion uses GSAP when available, while native <details> behavior
  // remains the no-script and reduced-motion fallback.
  if(window.gsap&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    document.querySelectorAll('.faq').forEach(function(faq){
      faq.classList.add('faq--gsap');
      faq.querySelectorAll('details').forEach(function(detail){
        var summary=detail.querySelector('summary'),answer=detail.querySelector('.ans');
        if(!summary||!answer)return;
        if(detail.open)window.gsap.set(answer,{height:'auto',opacity:1});
        summary.addEventListener('click',function(event){
          event.preventDefault();
          if(detail.dataset.animating==='true')return;
          detail.dataset.animating='true';
          if(detail.open){
            window.gsap.to(answer,{
              height:0,
              opacity:0,
              duration:.3,
              ease:'power2.inOut',
              onComplete:function(){
                detail.open=false;
                detail.dataset.animating='false';
                window.gsap.set(answer,{clearProps:'height,opacity'});
              }
            });
          }else{
            detail.open=true;
            window.gsap.fromTo(answer,{height:0,opacity:0},{
              height:'auto',
              opacity:1,
              duration:.36,
              ease:'power2.out',
              onComplete:function(){
                detail.dataset.animating='false';
                window.gsap.set(answer,{clearProps:'height,opacity'});
              }
            });
          }
        });
      });
    });
  }
  // restrained section reveals; content remains visible when JS is unavailable
  var revealTargets=document.querySelectorAll('.cms-page main > section');
  if('IntersectionObserver' in window&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    var revealObserver=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('is-visible');revealObserver.unobserve(entry.target);}});},{rootMargin:'0px 0px -8% 0px',threshold:.08});
    revealTargets.forEach(function(el){el.classList.add('reveal-section');revealObserver.observe(el);});
  }else{revealTargets.forEach(function(el){el.classList.add('is-visible');});}
  // toast
  var tHost=document.getElementById('toastHost');
  window.toast=function(msg){if(!tHost)return;var t=document.createElement('div');t.className='toast';t.innerHTML='<span class="ic kiker-icon kiker-icon--verified" aria-hidden="true"></span>'+msg;tHost.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateY(8px)';t.style.transition='opacity .2s,transform .2s';setTimeout(function(){t.remove();},220);},3400);};
  // Legacy CMS forms were authored with visible placeholders but, in several
  // blocks, without programmatic labels or field names. Preserve the visual
  // design while making every control understandable to assistive technology
  // and giving the submission endpoint stable payload keys.
  document.querySelectorAll('form').forEach(function(form){
    var usedNames={};
    form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]),select,textarea').forEach(function(el,index){
      var nearbyLabel=el.parentElement?el.parentElement.querySelector('label'):null;
      var visibleLabel=el.labels&&el.labels.length
        ?el.labels[0].textContent.trim()
        :(nearbyLabel?nearbyLabel.textContent.trim():'');
      var selectLabel=el.tagName==='SELECT'&&el.options.length?el.options[0].textContent.trim():'';
      var label=visibleLabel||el.getAttribute('aria-label')||el.placeholder||selectLabel||('Field '+(index+1));
      if(!(el.labels&&el.labels.length)&&!el.getAttribute('aria-label'))el.setAttribute('aria-label',label);
      if(!el.name){
        var base=label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'')||('field_'+(index+1));
        var name=base,suffix=2;
        while(usedNames[name])name=base+'_'+suffix++;
        el.name=name;
      }
      usedNames[el.name]=true;
    });
  });
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
