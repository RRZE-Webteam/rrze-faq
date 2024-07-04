(()=>{"use strict";var e={n:l=>{var a=l&&l.__esModule?()=>l.default:()=>l;return e.d(a,{a}),a},d:(l,a)=>{for(var r in a)e.o(a,r)&&!e.o(l,r)&&Object.defineProperty(l,r,{enumerable:!0,get:a[r]})},o:(e,l)=>Object.prototype.hasOwnProperty.call(e,l)};const l=window.wp.blocks,a=JSON.parse('{"UU":"create-block/rrze-faq"}'),r=window.React,t=window.wp.i18n,o=window.wp.element,n=window.wp.data,s=window.wp.blockEditor,i=window.wp.components,_=window.wp.serverSideRender;var c=e.n(_);(0,l.registerBlockType)(a.UU,{edit:function({attributes:e,setAttributes:l}){const{category:a,tag:_,id:d,hstart:u,order:b,sort:g,lang:f,additional_class:p,color:h,style:q,load_open:v,expand_all_link:z,hide_title:m,hide_accordion:C,glossarystyle:y,glossary:E}=e,S=(0,s.useBlockProps)(),[w,k]=(0,o.useState)([""]),[T,x]=(0,o.useState)([""]),[O,R]=(0,o.useState)([""]);(0,o.useEffect)((()=>{l({category:a}),l({tag:_}),l({id:d}),l({hstart:u}),l({order:b}),l({sort:g}),l({lang:f}),l({additional_class:p}),l({color:h}),l({style:q}),l({load_open:v}),l({expand_all_link:z}),l({hide_title:m}),l({hide_accordion:C}),l({glossarystyle:y}),l({glossary:E})}),[a,_,d,u,b,g,f,p,h,q,v,z,m,C,y,E,l]);const A=(0,n.useSelect)((e=>e("core").getEntityRecords("taxonomy","faq_category",{per_page:-1,orderby:"name",order:"asc",status:"publish",_fields:"id,name,slug"})),[]),P=[{label:(0,t.__)("all","rrze-faq"),value:""}];A&&Object.values(A).forEach((e=>{P.push({label:e.name,value:e.slug})}));const j=(0,n.useSelect)((e=>e("core").getEntityRecords("taxonomy","faq_tag",{per_page:-1,orderby:"name",order:"asc",status:"publish",_fields:"id,name,slug"})),[]),B=[{label:(0,t.__)("all","rrze-faq"),value:""}];j&&Object.values(j).forEach((e=>{B.push({label:e.name,value:e.slug})}));const D=(0,n.useSelect)((e=>e("core").getEntityRecords("postType","faq",{per_page:-1,orderby:"title",order:"asc",status:"publish",_fields:"id,title.rendered"})),[]),F=[{label:(0,t.__)("all","rrze-faq"),value:0}];D&&Object.values(D).forEach((e=>{F.push({label:e.title.rendered?e.title.rendered:(0,t.__)("No title","rrze-faq"),value:e.id})}));const I=[{label:(0,t.__)("all","rrze-faq"),value:""},{label:(0,t.__)("German","rrze-faq"),value:"de"},{label:(0,t.__)("English","rrze-faq"),value:"en"},{label:(0,t.__)("French","rrze-faq"),value:"fr"},{label:(0,t.__)("Spanish","rrze-faq"),value:"es"},{label:(0,t.__)("Russian","rrze-faq"),value:"ru"},{label:(0,t.__)("Chinese","rrze-faq"),value:"zh"}],U=[{label:(0,t.__)("none","rrze-faq"),value:""},{label:(0,t.__)("Categories","rrze-faq"),value:"category"},{label:(0,t.__)("Tags","rrze-faq"),value:"tag"}],G=[{label:(0,t.__)("A - Z","rrze-faq"),value:"a-z"},{label:(0,t.__)("Tagcloud","rrze-faq"),value:"tagcloud"},{label:(0,t.__)("Tabs","rrze-faq"),value:"tabs"},{label:(0,t.__)("-- hidden --","rrze-faq"),value:""}],H=[{label:(0,t.__)("none","rrze-faq"),value:""},{label:"light",value:"light"},{label:"dark",value:"dark"}],L=[{label:(0,t.__)("Title","rrze-faq"),value:"title"},{label:(0,t.__)("ID","rrze-faq"),value:"id"},{label:(0,t.__)("Sort field","rrze-faq"),value:"sortfield"}],N=[{label:(0,t.__)("ASC","rrze-faq"),value:"ASC"},{label:(0,t.__)("DESC","rrze-faq"),value:"DESC"}];return(0,r.createElement)(r.Fragment,null,(0,r.createElement)(s.InspectorControls,null,(0,r.createElement)(i.PanelBody,{title:(0,t.__)("Filter","rrze-faq")},(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Categories","rrze-faq"),value:w,options:P,onChange:e=>{k(e),l({category:String(e)})},multiple:!0}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Tags","rrze-faq"),value:T,options:B,onChange:e=>{x(e),l({tag:String(e)})},multiple:!0}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("FAQ","rrze-faq"),value:O,options:F,onChange:e=>{R(e),l({id:String(e)})},multiple:!0}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Language","rrze-faq"),options:I,onChange:e=>l({lang:e})}))),(0,r.createElement)(s.InspectorControls,{group:"styles"},(0,r.createElement)(i.PanelBody,{title:(0,t.__)("Styles","rrze-faq")},(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Glossary content","rrze-faq"),options:U,onChange:e=>l({glossary:e})}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Glossary style","rrze-faq"),options:G,onChange:e=>l({glossarystyle:e})}),(0,r.createElement)(i.ToggleControl,{checked:!!C,label:(0,t.__)("Hide accordion","rrze-faq"),onChange:()=>l({hide_accordion:!C})}),(0,r.createElement)(i.ToggleControl,{checked:!!m,label:(0,t.__)("Hide title","rrze-faq"),onChange:()=>l({hide_title:!m})}),(0,r.createElement)(i.ToggleControl,{checked:!!z,label:(0,t.__)('Show "expand all" button',"rrze-faq"),onChange:()=>l({expand_all_link:!z})}),(0,r.createElement)(i.ToggleControl,{checked:!!v,label:(0,t.__)("Load website with opened accordions","rrze-faq"),onChange:()=>l({load_open:!v})}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Color","rrze-faq"),options:[{label:"fau",value:"fau"},{label:"med",value:"med"},{label:"nat",value:"nat"},{label:"phil",value:"phil"},{label:"rw",value:"rw"},{label:"tf",value:"tf"}],onChange:e=>l({color:e})}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Style","rrze-faq"),options:H,onChange:e=>l({style:e})}),(0,r.createElement)(i.TextControl,{label:(0,t.__)("Additional CSS-class(es) for sourrounding DIV","rrze-faq"),onChange:e=>l({additional_class:e})}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Sort","rrze-faq"),options:L,onChange:e=>l({sort:e})}),(0,r.createElement)(i.SelectControl,{label:(0,t.__)("Order","rrze-faq"),options:N,onChange:e=>l({order:e})}),(0,r.createElement)(i.RangeControl,{label:(0,t.__)("Heading starts with...","rrze-faq"),onChange:e=>l({hstart:e}),min:2,max:6,initialPosition:2}))),(0,r.createElement)("div",{...S},(0,r.createElement)(c(),{block:"create-block/rrze-faq",attributes:e})))},save:function(){return null}})})();