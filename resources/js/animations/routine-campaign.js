import gsap from 'gsap';
export function initRoutineCampaign(root=document){
 const section=root.querySelector('[data-routine-campaign]');if(!section)return()=>{};
 const panels=[...section.querySelectorAll('[data-campaign-panel]')],tabs=[...section.querySelectorAll('[data-campaign-tab]')];
 const pause=section.querySelector('[data-campaign-pause]'),label=section.querySelector('[data-campaign-pause-label]');
 const reduce=matchMedia('(prefers-reduced-motion:reduce)'),ctx=gsap.context(()=>{},section),events=[];
 let index=0,timer=null,paused=false,inside=false,focused=false,hovered=false,dead=false,transition=null;
 const on=(el,event,fn)=>{el.addEventListener(event,fn);events.push(()=>el.removeEventListener(event,fn));};
 const stop=()=>{clearTimeout(timer);timer=null;};
 const schedule=()=>{stop();const playing=!dead&&!paused&&inside&&!focused&&!hovered&&!reduce.matches&&!document.hidden;section.dataset.playback=playing?'playing':'paused';if(playing)timer=setTimeout(()=>{section.dataset.autoAdvances=String(Number(section.dataset.autoAdvances||0)+1);show((index+1)%panels.length);},6500);};
 const settle=()=>panels.forEach((p,i)=>{p.classList.toggle('is-active',i===index);p.inert=i!==index;p.setAttribute('aria-hidden',String(i!==index));tabs[i].setAttribute('aria-selected',String(i===index));tabs[i].tabIndex=i===index?0:-1;});
 const show=next=>{
  if(next===index){schedule();return;}stop();transition?.kill();const previous=panels[index];index=next;settle();
  ctx.add(()=>{panels.forEach(p=>gsap.set(p,{opacity:p===previous?1:0,zIndex:p===previous?1:0}));const incoming=panels[index];
   if(reduce.matches){gsap.set(panels,{clearProps:'opacity,zIndex,transform'});schedule();return;}
   gsap.set(incoming,{zIndex:2});transition=gsap.timeline({onComplete:()=>{gsap.set(panels,{clearProps:'opacity,zIndex'});schedule();}})
    .to(incoming,{opacity:1,duration:.85,ease:'power2.inOut'},0)
    .fromTo(incoming.querySelector('img'),{scale:1.045},{scale:1,duration:1.6,ease:'power2.out',clearProps:'transform'},0)
    .fromTo(incoming.querySelector('.home-campaign__copy'),{y:18,clipPath:'inset(0 0 15% 0)'},{y:0,clipPath:'inset(0 0 0% 0)',duration:.9,ease:'power3.out',clearProps:'transform,clipPath'},.1);
  });
 };
 tabs.forEach((tab,i)=>{on(tab,'click',()=>show(i));on(tab,'keydown',e=>{let next=i;if(e.key==='ArrowRight')next=(i+1)%tabs.length;else if(e.key==='ArrowLeft')next=(i+tabs.length-1)%tabs.length;else if(e.key==='Home')next=0;else if(e.key==='End')next=tabs.length-1;else return;e.preventDefault();tabs[next].focus();show(next);});});
 on(pause,'click',()=>{paused=!paused;pause.setAttribute('aria-pressed',String(paused));pause.setAttribute('aria-label',paused?'Play campaign animation':'Pause campaign animation');label.textContent=paused?'Play':'Pause';schedule();});
 on(section,'mouseenter',()=>{hovered=true;stop();});on(section,'mouseleave',()=>{hovered=false;schedule();});
 on(section,'focusin',()=>{focused=true;stop();});on(section,'focusout',e=>{focused=section.contains(e.relatedTarget);schedule();});
 on(document,'visibilitychange',schedule);on(reduce,'change',()=>{transition?.kill();ctx.add(()=>gsap.set(panels,{clearProps:'opacity,zIndex'}));settle();schedule();});
 const observer=new IntersectionObserver(([entry])=>{inside=entry.isIntersecting;schedule();},{threshold:.3});observer.observe(section);settle();
 return()=>{dead=true;stop();observer.disconnect();transition?.kill();events.forEach(fn=>fn());ctx.revert();};
}
