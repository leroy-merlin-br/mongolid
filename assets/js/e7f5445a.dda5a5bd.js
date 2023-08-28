"use strict";(self.webpackChunkdocs=self.webpackChunkdocs||[]).push([[699],{3905:(e,t,r)=>{r.d(t,{Zo:()=>p,kt:()=>y});var n=r(7294);function o(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function a(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function c(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?a(Object(r),!0).forEach((function(t){o(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):a(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}function i(e,t){if(null==e)return{};var r,n,o=function(e,t){if(null==e)return{};var r,n,o={},a=Object.keys(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||(o[r]=e[r]);return o}(e,t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);for(n=0;n<a.length;n++)r=a[n],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(o[r]=e[r])}return o}var l=n.createContext({}),s=function(e){var t=n.useContext(l),r=t;return e&&(r="function"==typeof e?e(t):c(c({},t),e)),r},p=function(e){var t=s(e.components);return n.createElement(l.Provider,{value:t},e.children)},d="mdxType",u={inlineCode:"code",wrapper:function(e){var t=e.children;return n.createElement(n.Fragment,{},t)}},m=n.forwardRef((function(e,t){var r=e.components,o=e.mdxType,a=e.originalType,l=e.parentName,p=i(e,["components","mdxType","originalType","parentName"]),d=s(r),m=o,y=d["".concat(l,".").concat(m)]||d[m]||u[m]||a;return r?n.createElement(y,c(c({ref:t},p),{},{components:r})):n.createElement(y,c({ref:t},p))}));function y(e,t){var r=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var a=r.length,c=new Array(a);c[0]=m;var i={};for(var l in t)hasOwnProperty.call(t,l)&&(i[l]=t[l]);i.originalType=e,i[d]="string"==typeof e?e:o,c[1]=i;for(var s=2;s<a;s++)c[s]=r[s];return n.createElement.apply(null,c)}return n.createElement.apply(null,r)}m.displayName="MDXCreateElement"},6635:(e,t,r)=>{r.r(t),r.d(t,{assets:()=>l,contentTitle:()=>c,default:()=>u,frontMatter:()=>a,metadata:()=>i,toc:()=>s});var n=r(7462),o=(r(7294),r(3905));const a={},c="Legacy Record",i={unversionedId:"legacy/record",id:"legacy/record",title:"Legacy Record",description:"With LegacyRecord you can do pretty most the same actions that you can do with AbstractModel.",source:"@site/docs/legacy/record.md",sourceDirName:"legacy",slug:"/legacy/record",permalink:"/mongolid/docs/3.6.0/legacy/record",draft:!1,tags:[],version:"current",frontMatter:{},sidebar:"docsSidebar",previous:{title:"Embeds Relationships",permalink:"/mongolid/docs/3.6.0/legacy/embeds"},next:{title:"References Relationships",permalink:"/mongolid/docs/3.6.0/legacy/references"}},l={},s=[],p={toc:s},d="wrapper";function u(e){let{components:t,...r}=e;return(0,o.kt)(d,(0,n.Z)({},p,r,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"legacy-record"},"Legacy Record"),(0,o.kt)("p",null,"With ",(0,o.kt)("inlineCode",{parentName:"p"},"LegacyRecord")," you can do pretty most the same actions that you can do with ",(0,o.kt)("inlineCode",{parentName:"p"},"AbstractModel"),".\nHere you find the differences that ",(0,o.kt)("inlineCode",{parentName:"p"},"AbstractModel")," can't do anymore, but ",(0,o.kt)("inlineCode",{parentName:"p"},"LegacyRecord")," still can do for you."),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php",metastring:'title="Mass assignment"',title:'"Mass','assignment"':!0},"    $post = new Post;\n    $post->fill(['title' => 'Bacon']);\n")),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php",metastring:'title="Converting a model to json"',title:'"Converting',a:!0,model:!0,to:!0,'json"':!0},"    return User::find(1)->toJson();\n")))}u.isMDXComponent=!0}}]);