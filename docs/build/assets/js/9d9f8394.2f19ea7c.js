"use strict";(self.webpackChunkdocs=self.webpackChunkdocs||[]).push([[360],{3905:(e,n,t)=>{t.d(n,{Zo:()=>u,kt:()=>h});var r=t(7294);function o(e,n,t){return n in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function i(e,n){var t=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);n&&(r=r.filter((function(n){return Object.getOwnPropertyDescriptor(e,n).enumerable}))),t.push.apply(t,r)}return t}function a(e){for(var n=1;n<arguments.length;n++){var t=null!=arguments[n]?arguments[n]:{};n%2?i(Object(t),!0).forEach((function(n){o(e,n,t[n])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(t)):i(Object(t)).forEach((function(n){Object.defineProperty(e,n,Object.getOwnPropertyDescriptor(t,n))}))}return e}function s(e,n){if(null==e)return{};var t,r,o=function(e,n){if(null==e)return{};var t,r,o={},i=Object.keys(e);for(r=0;r<i.length;r++)t=i[r],n.indexOf(t)>=0||(o[t]=e[t]);return o}(e,n);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(r=0;r<i.length;r++)t=i[r],n.indexOf(t)>=0||Object.prototype.propertyIsEnumerable.call(e,t)&&(o[t]=e[t])}return o}var l=r.createContext({}),p=function(e){var n=r.useContext(l),t=n;return e&&(t="function"==typeof e?e(n):a(a({},n),e)),t},u=function(e){var n=p(e.components);return r.createElement(l.Provider,{value:n},e.children)},c={inlineCode:"code",wrapper:function(e){var n=e.children;return r.createElement(r.Fragment,{},n)}},d=r.forwardRef((function(e,n){var t=e.components,o=e.mdxType,i=e.originalType,l=e.parentName,u=s(e,["components","mdxType","originalType","parentName"]),d=p(t),h=o,m=d["".concat(l,".").concat(h)]||d[h]||c[h]||i;return t?r.createElement(m,a(a({ref:n},u),{},{components:t})):r.createElement(m,a({ref:n},u))}));function h(e,n){var t=arguments,o=n&&n.mdxType;if("string"==typeof e||o){var i=t.length,a=new Array(i);a[0]=d;var s={};for(var l in n)hasOwnProperty.call(n,l)&&(s[l]=n[l]);s.originalType=e,s.mdxType="string"==typeof e?e:o,a[1]=s;for(var p=2;p<i;p++)a[p]=t[p];return r.createElement.apply(null,a)}return r.createElement.apply(null,t)}d.displayName="MDXCreateElement"},9222:(e,n,t)=>{t.r(n),t.d(n,{assets:()=>l,contentTitle:()=>a,default:()=>c,frontMatter:()=>i,metadata:()=>s,toc:()=>p});var r=t(7462),o=(t(7294),t(3905));const i={sidebar_position:8},a="Troubleshooting",s={unversionedId:"troubleshooting",id:"troubleshooting",title:"Troubleshooting",description:"\"PHP Fatal error: Class 'MongoDB\\Client' not found in ...\"",source:"@site/docs/troubleshooting.md",sourceDirName:".",slug:"/troubleshooting",permalink:"/docs/3.2.0/troubleshooting",draft:!1,tags:[],version:"current",sidebarPosition:8,frontMatter:{sidebar_position:8},sidebar:"docsSidebar",previous:{title:"References Relationships",permalink:"/docs/3.2.0/legacy/references"}},l={},p=[{value:"&quot;PHP Fatal error: Class &#39;MongoDBClient&#39; not found in ...&quot;",id:"php-fatal-error-class-mongodbclient-not-found-in-",level:2},{value:"&quot;Class &#39;MongoDBClient&#39; not found in ...&quot; in CLI persists even with MongoDB driver installed.",id:"class-mongodbclient-not-found-in--in-cli-persists-even-with-mongodb-driver-installed",level:2},{value:"This package requires php &gt;=7.0 but your PHP version (X.X.X) does not satisfy that requirement.",id:"this-package-requires-php-70-but-your-php-version-xxx-does-not-satisfy-that-requirement",level:2}],u={toc:p};function c(e){let{components:n,...t}=e;return(0,o.kt)("wrapper",(0,r.Z)({},u,t,{components:n,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"troubleshooting"},"Troubleshooting"),(0,o.kt)("h2",{id:"php-fatal-error-class-mongodbclient-not-found-in-"},"\"PHP Fatal error: Class 'MongoDB\\Client' not found in ...\""),(0,o.kt)("p",null,"The ",(0,o.kt)("inlineCode",{parentName:"p"},"MongoDB\\Client")," class is contained in the ",(0,o.kt)("a",{parentName:"p",href:"http://pecl.php.net/package/mongodb"},(0,o.kt)("strong",{parentName:"a"},"new")," MongoDB driver")," for PHP.\n",(0,o.kt)("a",{parentName:"p",href:"http://www.php.net/manual/en/mongodb.installation.php"},"Here is an installation guide"),". The driver is a PHP extension\nwritten in C and maintained by ",(0,o.kt)("a",{parentName:"p",href:"https://mongodb.com"},"MongoDB"),". Mongolid and most other MongoDB PHP libraries utilize it\nin order to be fast and reliable."),(0,o.kt)("h2",{id:"class-mongodbclient-not-found-in--in-cli-persists-even-with-mongodb-driver-installed"},"\"Class 'MongoDB\\Client' not found in ...\" in CLI persists even with MongoDB driver installed."),(0,o.kt)("p",null,"Make sure that the ",(0,o.kt)("strong",{parentName:"p"},"php.ini")," file used in the CLI environment includes the MongoDB extension. In some systems, the\ndefault PHP installation uses different ",(0,o.kt)("strong",{parentName:"p"},".ini")," files for the web and CLI environments."),(0,o.kt)("p",null,"Run ",(0,o.kt)("inlineCode",{parentName:"p"},"php -i | grep 'Configuration File'")," in a terminal to check the ",(0,o.kt)("strong",{parentName:"p"},".ini")," that is being used."),(0,o.kt)("p",null,"To check if PHP in the CLI environment is importing the driver properly run ",(0,o.kt)("inlineCode",{parentName:"p"},"php -i | grep 'mongo'")," in your terminal.\nYou should get output similar to:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-shell"},"$ php -i | grep 'mongo'\nmongodb support => enabled\nmongodb version => 1.1.3\n")),(0,o.kt)("h2",{id:"this-package-requires-php-70-but-your-php-version-xxx-does-not-satisfy-that-requirement"},"This package requires php >=7.0 but your PHP version (X.X.X) does not satisfy that requirement."),(0,o.kt)("p",null,"The new (and improved) version 2.0 of Mongolid requires php7. If you are looking for the old PHP 5.x version, head to\nthe ",(0,o.kt)("a",{parentName:"p",href:"https://github.com/leroy-merlin-br/mongolid/tree/v0.8-dev"},"v0.8 branch"),"."))}c.isMDXComponent=!0}}]);