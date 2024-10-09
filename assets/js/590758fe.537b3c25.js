"use strict";(self.webpackChunkdocs=self.webpackChunkdocs||[]).push([[595],{3905:(e,t,n)=>{n.d(t,{Zo:()=>p,kt:()=>h});var r=n(7294);function o(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function a(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function i(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?a(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):a(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function s(e,t){if(null==e)return{};var n,r,o=function(e,t){if(null==e)return{};var n,r,o={},a=Object.keys(e);for(r=0;r<a.length;r++)n=a[r],t.indexOf(n)>=0||(o[n]=e[n]);return o}(e,t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);for(r=0;r<a.length;r++)n=a[r],t.indexOf(n)>=0||Object.prototype.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var l=r.createContext({}),d=function(e){var t=r.useContext(l),n=t;return e&&(n="function"==typeof e?e(t):i(i({},t),e)),n},p=function(e){var t=d(e.components);return r.createElement(l.Provider,{value:t},e.children)},c="mdxType",u={inlineCode:"code",wrapper:function(e){var t=e.children;return r.createElement(r.Fragment,{},t)}},m=r.forwardRef((function(e,t){var n=e.components,o=e.mdxType,a=e.originalType,l=e.parentName,p=s(e,["components","mdxType","originalType","parentName"]),c=d(n),m=o,h=c["".concat(l,".").concat(m)]||c[m]||u[m]||a;return n?r.createElement(h,i(i({ref:t},p),{},{components:n})):r.createElement(h,i({ref:t},p))}));function h(e,t){var n=arguments,o=t&&t.mdxType;if("string"==typeof e||o){var a=n.length,i=new Array(a);i[0]=m;var s={};for(var l in t)hasOwnProperty.call(t,l)&&(s[l]=t[l]);s.originalType=e,s[c]="string"==typeof e?e:o,i[1]=s;for(var d=2;d<a;d++)i[d]=n[d];return r.createElement.apply(null,i)}return r.createElement.apply(null,n)}m.displayName="MDXCreateElement"},4651:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>l,contentTitle:()=>i,default:()=>u,frontMatter:()=>a,metadata:()=>s,toc:()=>d});var r=n(7462),o=(n(7294),n(3905));const a={},i="References",s={unversionedId:"relationships/references",id:"relationships/references",title:"References",description:"In Mongolid a reference is made by storing the _id of the referenced object.",source:"@site/docs/relationships/references.md",sourceDirName:"relationships",slug:"/relationships/references",permalink:"/mongolid/docs/3.8.0/relationships/references",draft:!1,tags:[],version:"current",frontMatter:{},sidebar:"docsSidebar",previous:{title:"Embeds",permalink:"/mongolid/docs/3.8.0/relationships/embeds"},next:{title:"Casting attributes",permalink:"/mongolid/docs/3.8.0/casting"}},l={},d=[{value:"References One",id:"references-one",level:2},{value:"Defining A References One Relation",id:"defining-a-references-one-relation",level:3},{value:"References Many",id:"references-many",level:2},{value:"Defining A References Many Relation",id:"defining-a-references-many-relation",level:3}],p={toc:d},c="wrapper";function u(e){let{components:t,...n}=e;return(0,o.kt)(c,(0,r.Z)({},p,n,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("h1",{id:"references"},"References"),(0,o.kt)("p",null,"In Mongolid a reference is made by storing the ",(0,o.kt)("inlineCode",{parentName:"p"},"_id")," of the referenced object."),(0,o.kt)("p",null,"Referencing provides more flexibility than embedding;\nhowever, to resolve the references, client-side applications must issue follow-up queries.\nIn other words, using references requires more roundtrips to the server."),(0,o.kt)("p",null,"In general, use references when embedding would result in duplication of data and would not provide sufficient\nread performance advantages to outweigh the implications of the duplication.\nRead ",(0,o.kt)("a",{parentName:"p",href:"https://docs.mongodb.org/manual/tutorial/model-referenced-one-to-many-relationships-between-documents/"},"MongoDB - Relationships with Document References"),"\nto learn more how to take advantage of referencing in MongoDB."),(0,o.kt)("admonition",{type:"info"},(0,o.kt)("p",{parentName:"admonition"},"MongoDB ",(0,o.kt)("strong",{parentName:"p"},"relationships doesn't work like in a Relational database"),".\nIn MongoDB, data modeling decisions involve determining how to structure the documents to model the data effectively.")),(0,o.kt)("admonition",{type:"caution"},(0,o.kt)("p",{parentName:"admonition"},"If you try to create references between documents like you would do in a relational database you will end up with ",(0,o.kt)("em",{parentName:"p"},'"n+1 problem"')," and poor performance.")),(0,o.kt)("p",null,(0,o.kt)("a",{parentName:"p",href:"/mongolid/docs/3.8.0/legacy/references"},"Legacy References")," ",(0,o.kt)("em",{parentName:"p"},"For compatibility with version 2.x")),(0,o.kt)("hr",null),(0,o.kt)("h2",{id:"references-one"},"References One"),(0,o.kt)("h3",{id:"defining-a-references-one-relation"},"Defining A References One Relation"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"    class Post extends \\Mongolid\\Model\\AbstractModel {\n        protected $collection = 'posts';\n    \n        public function author()\n        {\n            // highlight-next-line\n            return $this->referencesOne(User::class, 'author');\n        }\n    \n    }\n    \n    class User extends \\Mongolid\\Model\\AbstractModel {\n        protected $collection = 'users';\n    }\n")),(0,o.kt)("p",null,"The first argument passed to the ",(0,o.kt)("inlineCode",{parentName:"p"},"referencesOne")," method is the name of the related model,\nthe second argument is the attribute where the referenced model ",(0,o.kt)("inlineCode",{parentName:"p"},"_id")," will be stored.\nOnce the relationship is defined, we may retrieve it using the following method:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"    $user = Post::find('4af9f23d8ead0e1d32000000')->author()->get();\n")),(0,o.kt)("admonition",{title:"Explanation:",type:"info"},(0,o.kt)("ul",{parentName:"admonition"},(0,o.kt)("li",{parentName:"ul"},"Query for the post with the ",(0,o.kt)("inlineCode",{parentName:"li"},"_id")," ",(0,o.kt)("em",{parentName:"li"},"'4af9f23d8ead0e1d32000000'")),(0,o.kt)("li",{parentName:"ul"},"Query for the user with the ",(0,o.kt)("inlineCode",{parentName:"li"},"_id")," equals to the ",(0,o.kt)("em",{parentName:"li"},"author")," attribute of the post that returns relationship object"),(0,o.kt)("li",{parentName:"ul"},"Get method to return the Author model filled"))),(0,o.kt)("p",null,"In order to set a reference to a document:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"    // The object that will be embedded\n    $user = new User();\n    $user->name = 'John';\n    $user->save() // This will populate the $user->_id\n    \n    // The object that will contain the user\n    $post = Post::first('4af9f23d8ead0e1d32000000');\n    \n    // This method will attach the $phone _id into the phone attribute of the user\n    // highlight-next-line\n    $post->author()->attach($user);\n    \n    $post->save();\n    \n    $post->author()->get(); // Will return a User object\n")),(0,o.kt)("admonition",{type:"info"},(0,o.kt)("p",{parentName:"admonition"},"When using Mongolid models you will need to call the ",(0,o.kt)("inlineCode",{parentName:"p"},"save()")," method after embedding or attaching objects.\nThe changes will only persist after you call the 'save()' method.")),(0,o.kt)("h2",{id:"references-many"},"References Many"),(0,o.kt)("p",null,"In Mongolid a ",(0,o.kt)("em",{parentName:"p"},"References Many")," is made by storing the ",(0,o.kt)("inlineCode",{parentName:"p"},"_id"),"s of the referenced objects."),(0,o.kt)("h3",{id:"defining-a-references-many-relation"},"Defining A References Many Relation"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"    class User extends \\Mongolid\\Model\\AbstractModel {\n        protected $collection = 'users';\n    \n        public function questions()\n        {\n            // highlight-next-line\n            return $this->referencesMany(Question::class, 'questions');\n        }\n    \n    }\n    \n    class Question extends \\Mongolid\\Model\\AbstractModel {\n        protected $collection = 'questions';\n    }\n")),(0,o.kt)("p",null,"The first argument passed to the ",(0,o.kt)("inlineCode",{parentName:"p"},"referencesMany")," method is the name of the related model,\nthe second argument is the attribute where the ",(0,o.kt)("inlineCode",{parentName:"p"},"_id"),"s will be stored.\nOnce the relationship is defined, we may retrieve it using the following method:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"    $posts = User::find('4af9f23d8ead0e1d32000000')->posts()->get();\n")),(0,o.kt)("admonition",{title:"Explanation:",type:"info"},(0,o.kt)("ul",{parentName:"admonition"},(0,o.kt)("li",{parentName:"ul"},"Query for the user with the ",(0,o.kt)("inlineCode",{parentName:"li"},"_id")," ",(0,o.kt)("em",{parentName:"li"},"'4af9f23d8ead0e1d32000000'")),(0,o.kt)("li",{parentName:"ul"},"Query for all the posts with the ",(0,o.kt)("inlineCode",{parentName:"li"},"_id")," in the user's ",(0,o.kt)("em",{parentName:"li"},"posts")," attribute and return a relationship object"),(0,o.kt)("li",{parentName:"ul"},"Method ",(0,o.kt)("inlineCode",{parentName:"li"},"get()")," will return the ",(0,o.kt)("a",{parentName:"li",href:"/mongolid/docs/3.8.0/cursor"},(0,o.kt)("inlineCode",{parentName:"a"},"Mongolid\\Cursor\\Cursor"))," with the related posts"))),(0,o.kt)("p",null,"In order to set a reference to a document use the ",(0,o.kt)("inlineCode",{parentName:"p"},"attach")," method. For example:"),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre",className:"language-php"},"    $postA = new Post();\n    $postA->title = 'Nice post';\n    \n    $postB = new Post();\n    $postB->title = 'Nicer post';\n    \n    $user = User::first('4af9f23d8ead0e1d32000000');\n    \n    // To attach a document\n    $user->posts()->attach($postA);\n    \n    // To attach many documents\n    $user->posts()->attachMany([$postA, $postB]);\n    \n    // To replace the current documents\n    $user->posts()->replace([$postA, $postB]);\n    \n    // To detach a single document\n    $user->posts()->detach($postA);\n    \n    // To detach all documents\n    $user->posts()->detachAll();\n    \n    $user->save();\n")),(0,o.kt)("admonition",{type:"info"},(0,o.kt)("p",{parentName:"admonition"},"When using Mongolid models you will need to call the ",(0,o.kt)("inlineCode",{parentName:"p"},"save()")," method after embedding or attaching objects.\nThe changes will only persist after you call the 'save()' method.")))}u.isMDXComponent=!0}}]);