import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'en',
  title: "Query Builder",
  srcDir: "content",
  cleanUrls: true,
  base: "/",
  metaChunk: false,
  head: [
    ['link', { rel: 'icon', href: '/images/logo.svg' }]
  ],
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/images//logo.svg',
    nav: [
      { text: 'Home', link: '/' },
    ],
    editLink: {
      pattern: 'https://gitlab.makina-corpus.net/php/query-builder/-/tree/main/docs/content/:path',
      text: 'Edit this page on Github'
    },
    docFooter: {
      prev: 'Previous page',
      next: 'Next page'
    },
    outlineTitle: 'On this page',
    lastUpdated: {
      text: 'Last updated',
      formatOptions: {
        dateStyle: 'short'
      }
    },
    search: {
      provider: 'local'
    },
    sidebar: [
      {
        text: 'Introduction',
        collapsed: false,
        items: [
          {
            text: 'Getting Started',
            link: '/introduction/getting-started',
            items: [
              { text: 'Standalone setup', link: '/introduction/getting-started#standalone-setup' },
              { text: 'Doctrine DBAL setup', link: '/introduction/getting-started#doctrine-dbal-setup' },
              { text: 'PDO setup', link: '/introduction/getting-started#pdo-setup' },
            ]
          },
          { text: 'Configuration', link: '/introduction/configuration' },
        ]
      },
      {
        text: 'Usage',
        collapsed: false,
        items: [
          {
            text: 'Introduction',
            link: '/usage',
            items: []
          },
        ]
      },
    ]
  }
})
