/* Kiker's U-Pull-It — shared behavior (every page loads this) */
(function(){
  // sticky nav border on scroll
  var nav=document.getElementById('nav');
  if(nav){var onScroll=function(){nav.classList.toggle('scrolled',window.scrollY>40);};window.addEventListener('scroll',onScroll,{passive:true});onScroll();}
  // mobile drawer
  var drawer=document.getElementById('drawer'),scrim=document.getElementById('scrim'),menu=document.getElementById('menuBtn');
  function openNav(){drawer.classList.add('open');scrim.classList.add('open');}
  function closeNav(){drawer.classList.remove('open');scrim.classList.remove('open');}
  if(menu&&drawer&&scrim){menu.onclick=openNav;scrim.onclick=closeNav;drawer.querySelectorAll('a').forEach(function(a){a.addEventListener('click',closeNav);});}
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
  // toast
  var tHost=document.getElementById('toastHost');
  window.toast=function(msg){if(!tHost)return;var t=document.createElement('div');t.className='toast';t.innerHTML='<span class="ic">✓</span>'+msg;tHost.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateY(8px)';t.style.transition='opacity .2s,transform .2s';setTimeout(function(){t.remove();},220);},3400);};
  function formSummary(form){
    return Array.prototype.map.call(form.elements,function(el){
      if(!el.name&&!el.placeholder&&!el.labels)return null;
      if(!el.value||el.type==='submit'||el.tagName==='BUTTON')return null;
      var label=(el.labels&&el.labels[0]?el.labels[0].textContent:el.name||el.placeholder).trim();
      return label+': '+el.value;
    }).filter(Boolean).join('\n');
  }
  window.submitOffer=function(e){e.preventDefault();window.location.href='https://www.kikersautoparts.com/sell-a-vehicle';return false;};
  window.submitMsg=function(e){
    e.preventDefault();
    var body=encodeURIComponent(formSummary(e.target));
    window.location.href='mailto:sales@kikersautoparts.com?subject=Website%20request&body='+body;
    return false;
  };
})();
