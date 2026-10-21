import gsap from 'gsap';
import {ScrollTrigger} from 'gsap/ScrollTrigger';
import {fitProductCutout,fitProductCutoutsIn} from './fit-product-cutout';
gsap.registerPlugin(ScrollTrigger);

/** A native-scroll driven, pinned collection on desktop; swipeable rail elsewhere. */
export function initFeatured(root=document) {
    const section=root.querySelector('[data-home-featured]');
    if(!section)return()=>{};
    const viewport=section.querySelector('[data-featured-viewport]');
    const track=section.querySelector('[data-featured-track]');
    const cards=[...section.querySelectorAll('[data-featured-card]')];
    if(!viewport||!track||!cards.length)return()=>{};
    const prev=section.querySelector('[data-featured-prev]'), next=section.querySelector('[data-featured-next]');
    const progress=section.querySelector('[data-featured-progress]'), hint=section.querySelector('[data-featured-hint]');
    const cleanups=[fitProductCutoutsIn(section,'[data-featured-media]','.home-featured__img',{fill:.8})];
    const fit=()=>section.querySelectorAll('[data-featured-media]').forEach(frame=>frame.querySelectorAll('.home-featured__img').forEach(img=>{if(img.complete&&img.naturalWidth)fitProductCutout(img,frame,{fill:.8});}));
    const nativeState=()=>{section.dataset.scrollMode='native';section.classList.remove('is-scroll-pinned');if(hint)hint.textContent='Swipe, drag, or use the arrows to explore';};
    nativeState();
    const mm=gsap.matchMedia();
    let pin=null, drag=null, moved=false;
    const max=()=>Math.max(0,viewport.scrollWidth-viewport.clientWidth);
    const step=()=>cards[0].getBoundingClientRect().width+(parseFloat(getComputedStyle(track).gap)||24);
    const sync=()=>{
        const maximum=max(), value=maximum?Math.min(1,Math.max(0,viewport.scrollLeft/maximum)):1;
        progress?.style.setProperty('--rail-progress',String(value));
        section.dataset.scrollProgress=value.toFixed(3);
        if(prev)prev.disabled=maximum===0||value<=.005;
        if(next)next.disabled=maximum===0||value>=.995;
    };
    const reduced=()=>matchMedia('(prefers-reduced-motion:reduce)').matches;
    const seek=(position, smooth=true)=>{
        const value=Math.max(0,Math.min(max(),position));
        if(pin && max()>0) {
            window.scrollTo({top:pin.start+(value/max())*(pin.end-pin.start),behavior:smooth&&!reduced()?'smooth':'auto'});
        } else viewport.scrollTo({left:value,behavior:smooth&&!reduced()?'smooth':'auto'});
    };
    const on=(el,event,fn,options)=>{el?.addEventListener(event,fn,options);cleanups.push(()=>el?.removeEventListener(event,fn,options));};
    on(prev,'click',()=>seek(viewport.scrollLeft-step()));
    on(next,'click',()=>seek(viewport.scrollLeft+step()));
    on(viewport,'scroll',sync,{passive:true});
    on(window,'resize',sync,{passive:true});
    on(viewport,'keydown',event=>{
        if(event.target.matches('input,textarea,select'))return;
        if(event.key==='ArrowLeft'||event.key==='ArrowRight'){event.preventDefault();seek(viewport.scrollLeft+(event.key==='ArrowRight'?step():-step()));}
    });
    on(viewport,'pointerdown',event=>{
        if(event.pointerType!=='mouse'||event.button!==0||event.target.closest('button'))return;
        drag={x:event.clientX,left:viewport.scrollLeft}; moved=false;
    });
    on(window,'pointermove',event=>{
        if(!drag)return;
        const dx=event.clientX-drag.x;
        if(Math.abs(dx)>6){moved=true;viewport.classList.add('is-dragging');seek(drag.left-dx,false);}
    });
    const release=()=>{drag=null;viewport.classList.remove('is-dragging');};
    on(window,'pointerup',release);on(window,'pointercancel',release);
    on(viewport,'click',event=>{if(moved){event.preventDefault();event.stopPropagation();moved=false;}},true);
    mm.add({desktop:'(min-width:1024px) and (min-height:700px)',reduce:'(prefers-reduced-motion:reduce)'},context=>{
        const enabled=context.conditions.desktop&&!context.conditions.reduce&&cards.length>2&&max()>0;
        section.dataset.scrollMode=enabled?'pinned':'native';
        section.classList.toggle('is-scroll-pinned',enabled);
        if(hint)hint.textContent=enabled?'Scroll to discover the collection':'Swipe, drag, or use the arrows to explore';
        if(enabled){
            const tween=gsap.fromTo(viewport,{scrollLeft:0},{scrollLeft:()=>max(),ease:'none',
                scrollTrigger:{id:'supplify-collection',trigger:section,
                    start:()=> 'top top+='+(document.querySelector('.site-header')?.offsetHeight||64),
                    end:()=>'+='+Math.max(600,max()*1.25),pin:true,pinSpacing:true,scrub:.45,
                    anticipatePin:1,invalidateOnRefresh:true,onUpdate:sync,onRefresh:()=>{fit();sync();}}});
            pin=tween.scrollTrigger;
        }
        sync();
        fit();
        return()=>{pin=null;nativeState();fit();};
    });
    const refresh=()=>ScrollTrigger.refresh();
    track.querySelectorAll('img').forEach(image=>{if(!image.complete)on(image,'load',refresh,{once:true});});
    document.fonts?.ready.then(()=>{if(section.isConnected)ScrollTrigger.refresh();});
    sync();
    return()=>{mm.revert();cleanups.forEach(fn=>fn());};
}
