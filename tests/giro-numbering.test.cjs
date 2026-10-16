const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function harness() {
  const elements=new Map(), requests=[], alerts=[];
  const checkbox={checked:true,value:'10',estado:1}, quantity={value:'3'};
  const row={checkbox,quantity};
  let hidden=0, refreshed=0, printed=0;
  function $(selector) {
    if (selector===row) return {find(s){const item=s.includes('checkbox')?checkbox:quantity;return {prop(k){return item[k];},val(){return item.value;},data(k){return item[k];}};}};
    if (selector==='.list-group-item') return {each(fn){fn.call(row);}};
    if (typeof selector !== 'string') return {on(){},ready(){}};
    if (!elements.has(selector)) elements.set(selector,{disabled:false,html:'Aceptar',classes:new Set()});
    const e=elements.get(selector);
    return {val(v){if(v!==undefined){e.value=v;return this;}return e.value??'1';},data(){return 7;},appendTo(){return this;},modal(){return this;},DataTable(){return {ajax:{reload(){}}};},addClass(k){e.classes.add(k);return this;},prop(k,v){if(v===undefined)return e[k];e[k]=v;return this;},html(v){e.html=v;return this;},
      removeClass(k){e.classes.delete(k);return this;},hide(){e.hidden=true;return this;},off(){e.off=true;return this;},
      on(event,fn){e.handler=fn;return this;}};
  }
  $.ajax = opts => {
    const r={opts,done(f){this.success=f;return this;},fail(f){this.failure=f;return this;},always(f){this.finish=f;return this;}};
    requests.push(r);return r;
  };
  const context=vm.createContext({$,document:{getElementById(){return null;},addEventListener(){}},console,window:{location:{}},
    Swal:{fire(o){alerts.push(o);return {then(fn){if(o.icon==='success')fn({isConfirmed:true});}};}},
    bootstrap:{Modal:class {show(){} hide(){hidden++;}}}});
  vm.runInContext(fs.readFileSync('view/giros_ciudadano/usudetalleciudadano.js','utf8'),context);
  context.getdataload=()=>{};
  context.listarTasas=()=>{context.emisionRequiereActualizar=false;context.calcularTotalTasas();};context.listarGiros=()=>{};context.recargarTabla=()=>{};
  context.calcularTotalTasas=()=>{refreshed++;$('#IDpagarGrupo').prop('disabled',false).html('Girar en Grupo');};
  context.imprimir=()=>printed++;context.redirect_by_post=()=>printed++;
  return {context,elements,requests,alerts,
    start(route){route.endsWith('grupo')?context.pagargrupo():context.pagar(10,7,1);elements.get('#btn-action').handler({preventDefault(){}});},
    state(){return {hidden,refreshed,printed};}};
}
for (const route of ['Pagar_Orden_Giro','Pagar_Orden_Giro_grupo']) {
  for (const failure of ['500','timeout','parsererror','malformed-success']) test(`${route}: ${failure} recovers without retries/success`,()=>{
    const h=harness(); let success=0, refresh=0;
    h.context.solicitarEmision(route,{id:1},()=>success++,()=>refresh++);
    h.context.solicitarEmision(route,{id:1},()=>success++,()=>refresh++);
    assert.equal(h.requests.length,1,'Duplicate request suppressed');
    const r=h.requests[0];
    assert.equal(r.opts.dataType,'json');assert.equal(r.opts.timeout,30000);
    if(failure==='malformed-success')r.success('not-json');else r.failure({status:failure==='500'?500:0},failure);
    r.finish();
    assert.equal(success,0);assert.equal(refresh,1);assert.equal(h.requests.length,1);
    assert.equal(h.elements.get('#btn-action').disabled,false);
    assert.equal(h.elements.get('#btn-action').off,true,'Old confirmation cannot resubmit');
    assert.equal(h.elements.get('#loadingSpinner').hidden,true);
    assert.equal(h.alerts[0].icon,'error');
  });
  test(`${route}: accepts parsed success exactly once`,()=>{
    const h=harness();let success=0;
    h.context.solicitarEmision(route,{},()=>success++,()=>{});
    h.requests[0].success(route.endsWith('grupo')?{success:true}:{error:false});h.requests[0].finish();
    assert.equal(success,1);assert.equal(h.alerts.length,0);
  });
}

for (const route of ['Pagar_Orden_Giro','Pagar_Orden_Giro_grupo']) {
  for (const outcome of ['500','timeout','parsererror','success','expired']) test(`public ${route}: ${outcome}`,()=>{
    const h=harness();h.start(route);
    assert.equal(h.requests.length,1);
    const r=h.requests[0];assert.ok(r.opts.url.endsWith(route));
    assert.equal(h.elements.get('#btn-action').disabled,true);
    if(outcome==='success')r.success(route.endsWith('grupo')?{success:true}:{error:false});
    else if(outcome==='expired')r.success({error:true,message:'La sesión ha expirado'});
    else r.failure({},outcome);
    r.finish();
    assert.equal(h.elements.get('#btn-action').disabled,false);
    assert.equal(h.elements.get('#btn-cancel').classes.has('disabled'),false);
    assert.equal(h.state().hidden,1);
    assert.equal(h.state().refreshed,1);
    assert.equal(h.state().printed,outcome==='success'?1:0);
    assert.equal(h.requests.length,1,'No automatic issuance retry');
    if(outcome==='expired')assert.equal(h.alerts[0].text,'La sesión ha expirado');
  });
}
