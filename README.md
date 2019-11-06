[Justuno](https://www.justuno.com) module for Magento 1.

## How to install or update

```
rm -rf app/code/community/Justuno ;
rm -rf app/design/adminhtml/default/default/template/justuno ;
rm -rf app/design/frontend/base/default/layout/justuno ;
rm -rf app/design/frontend/base/default/template/justuno  ;
ORG=justuno-com ;
REPO=m1 ;
FILE=$REPO.tar.gz ;
VERSION=$(curl -s https://api.github.com/repos/$ORG/$REPO/releases | grep tag_name | head -n 1 | cut -d '"' -f 4) ;
curl -L -o $FILE https://github.com/$ORG/$REPO/archive/$VERSION.tar.gz ;
tar xzvf $FILE ;
rm -f $FILE ;
cp -r $REPO-$VERSION/* . ;
rm -rf $REPO-$VERSION 
```
