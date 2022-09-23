"use strict";(self.webpackChunkdocs=self.webpackChunkdocs||[]).push([[848],{3905:(e,r,t)=>{t.d(r,{Zo:()=>p,kt:()=>m});var n=t(7294);function o(e,r,t){return r in e?Object.defineProperty(e,r,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[r]=t,e}function a(e,r){var t=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);r&&(n=n.filter((function(r){return Object.getOwnPropertyDescriptor(e,r).enumerable}))),t.push.apply(t,n)}return t}function s(e){for(var r=1;r<arguments.length;r++){var t=null!=arguments[r]?arguments[r]:{};r%2?a(Object(t),!0).forEach((function(r){o(e,r,t[r])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(t)):a(Object(t)).forEach((function(r){Object.defineProperty(e,r,Object.getOwnPropertyDescriptor(t,r))}))}return e}function i(e,r){if(null==e)return{};var t,n,o=function(e,r){if(null==e)return{};var t,n,o={},a=Object.keys(e);for(n=0;n<a.length;n++)t=a[n],r.indexOf(t)>=0||(o[t]=e[t]);return o}(e,r);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);for(n=0;n<a.length;n++)t=a[n],r.indexOf(t)>=0||Object.prototype.propertyIsEnumerable.call(e,t)&&(o[t]=e[t])}return o}var l=n.createContext({}),u=function(e){var r=n.useContext(l),t=r;return e&&(t="function"==typeof e?e(r):s(s({},r),e)),t},p=function(e){var r=u(e.components);return n.createElement(l.Provider,{value:r},e.children)},c={inlineCode:"code",wrapper:function(e){var r=e.children;return n.createElement(n.Fragment,{},r)}},d=n.forwardRef((function(e,r){var t=e.components,o=e.mdxType,a=e.originalType,l=e.parentName,p=i(e,["components","mdxType","originalType","parentName"]),d=u(t),m=o,f=d["".concat(l,".").concat(m)]||d[m]||c[m]||a;return t?n.createElement(f,s(s({ref:r},p),{},{components:t})):n.createElement(f,s({ref:r},p))}));function m(e,r){var t=arguments,o=r&&r.mdxType;if("string"==typeof e||o){var a=t.length,s=new Array(a);s[0]=d;var i={};for(var l in r)hasOwnProperty.call(r,l)&&(i[l]=r[l]);i.originalType=e,i.mdxType="string"==typeof e?e:o,s[1]=i;for(var u=2;u<a;u++)s[u]=t[u];return n.createElement.apply(null,s)}return n.createElement.apply(null,t)}d.displayName="MDXCreateElement"},5673:(e,r,t)=>{t.r(r),t.d(r,{assets:()=>l,contentTitle:()=>s,default:()=>c,frontMatter:()=>a,metadata:()=>i,toc:()=>u});var n=t(7462),o=(t(7294),t(3905));const a={sidebar_position:2},s="Mongolid Cursor",i={unversionedId:"cursor",id:"cursor",title:"Mongolid Cursor",description:"In MongoDB, a cursor is used to iterate through the results of a database query.",source:"@site/docs/cursor.md",sourceDirName:".",slug:"/cursor",permalink:"/mongolid/docs/3.2.0/cursor",draft:!1,tags:[],version:"current",sidebarPosition:2,frontMatter:{sidebar_position:2},sidebar:"docsSidebar",previous:{title:"Quick Start",permalink:"/mongolid/docs/3.2.0/quick-start"},next:{title:"Operations",permalink:"/mongolid/docs/3.2.0/operations"}},l={},u=[],p={toc:u};function c(e){let{components:r,...t}=e;return(0,o.kt)("wrapper",(0,n.Z)({},p,t,{components:r,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"mongolid-cursor"},"Mongolid Cursor"),(0,o.kt)("p",null,"In MongoDB, a cursor is used to iterate through the results of a database query.\nFor example, to query the database and see all results:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php",metastring:'title="Query database"',title:'"Query','database"':!0},"    $cursor = User::where(['kind' => 'visitor']);\n")),(0,o.kt)("p",null,"In the above example, the ",(0,o.kt)("inlineCode",{parentName:"p"},"$cursor")," variable will be a ",(0,o.kt)("inlineCode",{parentName:"p"},"Mongolid\\Cursor\\Cursor"),"."),(0,o.kt)("p",null,"The Mongolid's ",(0,o.kt)("inlineCode",{parentName:"p"},"Cursor")," wraps the original ",(0,o.kt)("inlineCode",{parentName:"p"},"MongoDB\\Driver\\Cursor")," object of the new MongoDB Driver\nin a way that you can build queries in a more fluent and easy way.\nAlso, the Mongolid's ",(0,o.kt)("inlineCode",{parentName:"p"},"Cursor")," will make sure to return the instances of your model instead of stdClass or arrays."),(0,o.kt)("admonition",{type:"info"},(0,o.kt)("p",{parentName:"admonition"},"The ",(0,o.kt)("a",{parentName:"p",href:"http://php.net/manual/en/class.mongodb-driver-cursor.php"},"Cursor class of the new driver")," is not as user-friendly as the old one.\nMongolid's cursor also make it as easy to use as the old one.")),(0,o.kt)("p",null,"The ",(0,o.kt)("inlineCode",{parentName:"p"},"Mongolid\\Cursor\\Cursor")," object has a lot of methods that helps you to iterate, refine and get information.\nFor example:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php",metastring:'title="Using cursor"',title:'"Using','cursor"':!0},"    $cursor = User::where(['kind'=>'visitor']);\n    \n    // Sorts the results by given fields. In the example bellow, it sorts by username DESC\n    $cursor->sort(['username'=>-1]);\n    \n    // Limits the number of results returned.\n    $cursor->limit(10);\n    \n    // Skips a number of results. Good for pagination\n    $cursor->skip(20);\n    \n    // Checks if the cursor is reading a valid result.\n    $cursor->valid();\n    \n    // Returns the first result\n    $cursor->first();\n")),(0,o.kt)("p",null,"You can also chain some methods:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php",metastring:'title="Chaining methods"',title:'"Chaining','methods"':!0},"    $page = 2;\n    \n    // In order to display 10 results per page\n    $cursor = User::all()->sort(['_id'=>1])->skip(10 * $page)->limit(10);\n    \n    // Then iterate through it\n    foreach($cursor as $user) {\n        // do something\n    }\n")))}c.isMDXComponent=!0}}]);