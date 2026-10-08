document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',(e)=>{
    const btn=e.submitter || form.querySelector('button[type="submit"]');
    if(btn && !btn.dataset.loading){
      btn.dataset.loading='1';
      // Jangan mengubah name/value submitter sebelum browser menyelesaikan submit.
      setTimeout(()=>{btn.disabled=true;},0);
    }
  }));
  document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{
    if(!confirm(el.dataset.confirm)) e.preventDefault();
  }));
  document.querySelectorAll('.animate-number').forEach(el=>{
    const target=parseInt(el.textContent||'0',10); let n=0;
    const step=Math.max(1,Math.ceil(target/24));
    el.textContent='0';
    const timer=setInterval(()=>{n=Math.min(target,n+step);el.textContent=n;if(n>=target)clearInterval(timer)},25);
  });
});