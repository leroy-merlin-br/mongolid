// @ts-check
// Note: type annotations allow type checking and IDEs autocompletion

const lightCodeTheme = require('prism-react-renderer/themes/github');
const darkCodeTheme = require('prism-react-renderer/themes/dracula');

/** @type {import('@docusaurus/types').Config} */
const config = {
    customFields: {
        currentVersion: '3.2.0',
    },

    title: 'MongoLid Doc',
    tagline: 'Mongolid ODM (Object Document Mapper)',
    url: 'https://leroy-merlin-br.github.io/',
    baseUrl: '/',
    onBrokenLinks: 'throw',
    onBrokenMarkdownLinks: 'warn',
    favicon: 'img/favicon.ico',

    // GitHub pages deployment config.
    // If you aren't using GitHub pages, you don't need these.
    organizationName: 'leroy-merlin-br', // Usually your GitHub org/user name.
    projectName: 'mongolid', // Usually your repo name.

    // Even if you don't use internalization, you can use this field to set useful
    // metadata like html lang. For example, if your site is Chinese, you may want
    // to replace "en" with "zh-Hans".
    i18n: {
        defaultLocale: 'en',
        locales: ['en'],
    },

    presets: [
        [
            'classic',
            /** @type {import('@docusaurus/preset-classic').Options} */
            ({
                docs: {
                    path: 'docs',
                    routeBasePath: 'docs',
                    sidebarPath: require.resolve('./sidebars.js'),
                    lastVersion: 'current',
                    versions: {
                        current: {
                            label: '3.2.0',
                            path: '3.2.0',
                        },
                    },
                    // // Please change this to your repo.
                    // // Remove this to remove the "edit this page" links.
                    // editUrl:
                    //     'https://github.com/facebook/docusaurus/tree/main/packages/create-docusaurus/templates/shared/',
                },
                // blog: {
                //     showReadingTime: true,
                //     // Please change this to your repo.
                //     // Remove this to remove the "edit this page" links.
                //     editUrl:
                //         'https://github.com/facebook/docusaurus/tree/main/packages/create-docusaurus/templates/shared/',
                // },
                theme: {
                    customCss: require.resolve('./src/css/custom.css'),
                },
            }),
        ],
    ],

    plugins: [
        [
            'content-docs',
            /** @type {import('@docusaurus/plugin-content-docs').Options} */
            ({
                id: 'about',
                path: 'about',
                routeBasePath: 'about',
            }),
        ],
    ],

    themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
        ({
            navbar: {
                title: 'Mongolid',
                logo: {
                    alt: 'MongoLid ODM',
                    src: 'img/mongolid.svg',
                },
                items: [
                    {
                        type: 'doc',
                        docId: 'quick-start',
                        position: 'left',
                        label: 'Get started',
                    },
                    {
                        to: '/about/license',
                        label: 'About',
                        position: 'left',
                        activeBaseRegex: `/about/`,
                    },
                    {
                        type: 'docsVersionDropdown',
                        position: 'right',
                    },
                    {
                        href: 'https://github.com/leroy-merlin-br/mongolid',
                        label: 'GitHub',
                        position: 'right',
                    },
                ],
            },
            footer: {
                style: 'dark',
                links: [
                    {
                        title: 'Related Projects',
                        items: [
                            {
                                label: 'MongoLid Laravel',
                                to: 'https://github.com/leroy-merlin-br/mongolid-laravel',
                            },
                            {
                                label: 'MongoLid Passport',
                                to: 'https://github.com/leroy-merlin-br/mongolid-passport',
                            },
                        ],
                    },
                    {
                        title: 'Where we are',
                        items: [
                            {
                                label: 'Ecommerce',
                                href: 'https://www.leroymerlin.com.br',
                            },
                            {
                                label: 'Twitter @LeroyMerlinBRA',
                                href: 'https://twitter.com/LeroyMerlinBRA',
                            },
                            {
                                label: 'Part of group Adeo',
                                href: 'https://github.com/enterprises/groupe-adeo',
                            },
                        ],
                    },
                    {
                        title: 'More',
                        items: [
                            {
                                label: 'GitHub',
                                href: 'https://leroy-merlin-br.github.io/mongolid',
                            },
                        ],
                    },
                ],
                copyright: `Copyright © ${new Date().getFullYear()} MongoLid Doc, Inc. Built with Docusaurus.`,
            },
            prism: {
                // theme: require('prism-react-renderer/themes/dracula'),
                theme: lightCodeTheme,
                darkTheme: darkCodeTheme,
                additionalLanguages: ['php'],
            },
        }),
};

module.exports = config;
