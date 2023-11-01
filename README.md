# FAQ GraphQL

**FAQ GraphQL is a part of MageINIC FAQ extension that adds GraphQL features.** This extension extends FAQ definitions.

## 1. How to install

Run the following command in Magento 2 root folder:

```
composer require mageinic/faq-graphql

php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

**Note:**
Magento 2 FAQ GraphQL requires installing [MageINIC FAQ](https://github.com/mageinic/Product-FAQ) in your Magento installation.

**Or Install via composer [Recommend]**
```
composer require mageinic/faq
```

## 2. How to use

- To view the queries that the **MageINIC FAQ GraphQL** extension supports, you can check `FaqGraphQl User Guide.pdf` Or run `FaqGraphQL.json` in Postman.

## 3. Get Support

- Feel free to [contact us](https://www.mageinic.com/contact.html) if you have any further questions.
- Like this project, Give us a **Star**
