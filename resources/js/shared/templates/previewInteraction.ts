/** Used by sandboxed library previews and the generated local preview gallery. */
export const previewFlowInteraction = `
function previewFollow(href){
 if(/^#preview-(?:step|payment)-[0-9]+$/.test(href)){
  const index=Number(href.split('-').pop());
  if(parent!==window)parent.postMessage({kind:'template-preview-step',index:index},'*');
  else {const url=new URL(location.href);url.searchParams.set('page',String(index));location.assign(url.href);}
  return true;
 }
 if(/^\\?template=[a-z0-9-]+&page=[0-9]+$/.test(href)){location.assign(href);return true;}
 return false;
}
document.addEventListener('submit',function(event){
 event.preventDefault();event.stopImmediatePropagation();
 const href=event.target.getAttribute('data-fnl-preview-next');
 if(href&&previewFollow(href))return;
 notice('Demo submission successful. No information was sent or saved.');
},true);
document.addEventListener('click',function(event){
 const control=event.target.closest('button[data-fnl-preview-next],a[data-fnl-preview-next],a[href^="#preview-payment-"]');
 if(!control)return;event.preventDefault();event.stopImmediatePropagation();
 const href=control.getAttribute('data-fnl-preview-next')||control.getAttribute('href');
 if(href&&previewFollow(href))return;
 notice('You reached the end of this demo. No payment or booking was made.');
},true);
`;
