/** Swap only after the alternate image loads; a missing/broken image keeps zoom. */
export function initProductHover(root=document){
 const cleanups=[];
 root.querySelectorAll('.collection-product__scene,.home-explore .product-tile__media').forEach(frame=>{
  const image=frame.querySelector('[data-hover-secondary],.product-tile__img--secondary');
  if(!image)return;
  const primary=frame.querySelector('.home-featured__img--primary,.product-tile__img--primary');
  const update=()=>frame.classList.toggle('is-secondary-ready',image.complete&&image.naturalWidth>0&&image.currentSrc!==primary?.currentSrc);
  image.addEventListener('load',update);image.addEventListener('error',update);update();
  cleanups.push(()=>{image.removeEventListener('load',update);image.removeEventListener('error',update);frame.classList.remove('is-secondary-ready');});
 });return()=>cleanups.forEach(fn=>fn());
}
