import gsap from 'gsap';
import {ScrollTrigger} from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);
export function initHomePolish(root=document){
    const shell=root.querySelector('.home-shell');if(!shell)return()=>{};
    const mm=gsap.matchMedia();
    mm.add('(prefers-reduced-motion:no-preference)',()=>{
        const events=[];const late=gsap.context(()=>{},shell);
        const bind=(el,event,fn)=>{el.addEventListener(event,fn);events.push(()=>el.removeEventListener(event,fn));};
        const titles=shell.querySelectorAll('#goals-heading,#featured-heading,#approach-heading,#daily-picks-heading,#why-supplify-heading,#education-heading');
        titles.forEach(title=>gsap.fromTo(title,{y:12,opacity:0},{y:0,opacity:1,duration:.6,ease:'power2.out',clearProps:'transform,opacity',scrollTrigger:{trigger:title,start:'top 92%',once:true}}));
        const scene=shell.querySelector('[data-editorial-scene]');
        if(scene){
            const image=scene.querySelector('img');
            gsap.fromTo(scene,{clipPath:'inset(16% 0 16% 0)'},{clipPath:'inset(0 0 0% 0)',duration:1.2,ease:'power3.out',clearProps:'clipPath',scrollTrigger:{trigger:scene,start:'top 85%',once:true}});
            if(image)gsap.fromTo(image,{scale:1.09},{scale:1,ease:'none',scrollTrigger:{trigger:scene,start:'top bottom',end:'bottom top',scrub:.7}});
        }
        shell.querySelectorAll('.goal-editorial__link').forEach((link,i)=>gsap.fromTo(link,{'--rule-scale':0},{'--rule-scale':1,duration:.75,delay:(i%2)*.08,ease:'power3.out',scrollTrigger:{trigger:link,start:'top 94%',once:true}}));
        shell.querySelectorAll('.goal-editorial__link strong,.goal-editorial__link small').forEach(text=>gsap.fromTo(text,{y:16,clipPath:'inset(0 0 100% 0)'},{y:0,clipPath:'inset(0 0 0% 0)',duration:.85,ease:'power3.out',clearProps:'transform,clipPath',scrollTrigger:{trigger:text,start:'top 92%',once:true}}));
        shell.querySelectorAll('.home-campaign__stage').forEach(stage=>gsap.fromTo(stage,{clipPath:'inset(12% 0 12% 0)'},{clipPath:'inset(0 0 0% 0)',duration:1.2,ease:'power3.out',clearProps:'clipPath',scrollTrigger:{trigger:stage,start:'top 88%',once:true}}));
        const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{
            if(!entry.isIntersecting)return;observer.unobserve(entry.target);
            const frame=entry.target;late.add(()=>{
                gsap.fromTo(frame,{clipPath:'inset(0 0 24% 0)'},{clipPath:'inset(0 0 0% 0)',duration:.9,ease:'power3.out',clearProps:'clipPath'});
                const image=frame.querySelector('img:not(.is-cutout-fitted)');
                if(image)gsap.fromTo(image,{scale:1.08},{scale:1,duration:1.2,ease:'power3.out',clearProps:'transform'});
            });
        }),{threshold:.15});
        shell.querySelectorAll('.home-explore .product-tile__media,.home-education__media').forEach(frame=>observer.observe(frame));
        if(matchMedia('(hover:hover) and (pointer:fine)').matches){
            shell.querySelectorAll('.home-education__card').forEach(card=>{
                const image=card.querySelector('img');if(!image)return;
                bind(card,'pointerenter',()=>late.add(()=>gsap.to(image,{scale:1.035,duration:.6,ease:'power2.out',overwrite:'auto'})));
                bind(card,'pointerleave',()=>late.add(()=>gsap.to(image,{scale:1,duration:.7,ease:'power2.out',overwrite:'auto'})));
            });
        }
        const form=shell.querySelector('.home-newsletter__form');
        if(form)gsap.fromTo(form,{'--input-line':0},{'--input-line':1,duration:1,ease:'power3.inOut',scrollTrigger:{trigger:form,start:'top 90%',once:true}});
        return()=>{observer.disconnect();events.forEach(fn=>fn());late.revert();};
    });return()=>mm.revert();
}
