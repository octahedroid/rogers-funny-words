const env = process.env.BACKEND_ENV;
const site = process.env.BACKEND_SITE;
const url = `https://${env}-${site}.pantheonsite.io`;
const api = `${url}/wp-json`;

const settings = {
  "name": "frontity",
  "state": {
    "frontity": {
      "url": "https://test.frontity.io",
      "title": "Test Frontity Blog",
      "description": "WordPress installation for Frontity development"
    }
  },
  "packages": [
    {
      "name": "@frontity/twentytwenty-theme",
      "state": {
        "theme": {
          "menu": [
            [
              "Home",
              "/"
            ]
          ],
          "featured": {
            "showOnList": false,
            "showOnPost": false
          }
        }
      }
    },
    {
      "name": "@frontity/wp-source",
      "state": {
        "source": {
          "api": api
        }
      }
    },
    "@frontity/tiny-router",
    "@frontity/html2react"
  ]
};

export default settings;
