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
        text: 'Basics',
        collapsed: false,
        items: [
          {
            text: 'Query basics',
            link: '/query/basics',
            items: []
          },
          {
            text: 'Parameter placeholder',
            link: '/query/placeholder',
            items: [
              { text: 'Values type-hinting', link: '/query/placeholder#placeholder-value-type-hinting' },
              { text: 'Dynamic identifier escaping', link: '/query/placeholder#placeholder-identifier-escaping' },
              { text: 'Nested expression', link: '/query/placeholder#placeholder-and-expressions' },
            ]
          },
          {
            text: 'Values',
            link: '/query/values',
            items: [
              { text: 'Value expression', link: '/query/values#general' },
              { text: 'Constant row', link: '/query/values#constant-row' },
              { text: 'Constant table', link: '/query/values#constant-table' },
            ]
          },
          {
            text: 'Typing and casting',
            link: '/query/cast',
            items: [
              { text: 'In raw SQL', link: '/query/cast#typing-values-in-raw-sql' },
              { text: 'In query builder', link: '/query/cast#typing-values-in-query-builder' },
              { text: 'SQL Cast', link: '/query/cast#sql-cast' },
            ]
          },
          {
            text: 'Value converter',
            link: '/converter/converter',
            items: []
          },
          {
            text: 'Data types',
            link: '/query/datatype',
            items: []
          },
        ]
      },
      {
        text: 'Query builder',
        collapsed: false,
        items: [
          {
            text: 'Select',
            link: '/query/impl',
            items: []
          },
          {
            text: 'Insert',
            link: '/query/impl',
            items: []
          },
          {
            text: 'Insert as select',
            link: '/query/impl',
            items: []
          },
          {
            text: 'Update',
            link: '/query/impl',
            items: []
          },
          {
            text: 'Delete',
            link: '/query/impl',
            items: []
          },
          {
            text: 'Merge',
            link: '/query/impl',
            items: []
          },
          {
            text: 'Where',
            link: '/query/impl/where',
            items: []
          },
        ]
      },
    ]
  }
})
