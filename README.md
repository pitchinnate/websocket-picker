# websocket-picker

Got this idea from a Meetup I go to. Every meeting they have a few giveaways and some guys made PickMe (http://pickme.io).
Basically it allows people to simply visit a link and they get added to the system of people wanting to get picked.
Then someone with the admin window up clicks a pick button and one person gets a greeen screen and everyone else
gets a red screen. I had been wanting to create something with WebSockets for awhile so I figured this would be a
fun project to reproduce their functionaility.

This package is built using Ratchet (http://socketo.me/) on the backend for the Websocket server and right now just
vanilla js on the frontend. I also use http://avatars.adorable.io/ to generate the random avatars it assigns to each
person.

### Example
I have a working example running at http://picker.eboodevelopment.com/ to access the admin view
just go to http://picker.eboodevelopment.com/#admin 

## Running the Websocket Server
To get it running first you need to get all dependencies so run `composer install`. Then you can simply go to the
project directory and run
```
php app/server.php
```
By default the websocket server will run on port 8282, if you already have something running on that port you can update
it in `app/server.php` edit this line:
```php
...
new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8282 //<-EDIT THIS
);
...
```
and on `web/index.hml` update this line:
```javascript
...
var conn = new WebSocket('ws://localhost:8282'); //<-EDIT THIS LINE
var body = $('body');
...
```

## Access the Frontend
You should then be able to open up a browser and open up the `/web/index.html` and it should work.

## Admin Panel
To access the system as an admin simply add `#admin` to the end of the url. Example: `http:/localhost/web/index.html#admin`
