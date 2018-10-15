# sun - A command-line php/www rest tool for calculating sun position, sunlight phases (times for sunrise, sunset, dusk, etc.)

CLI script `sun.php` instantiates class [gregseth/suncalc-php](https://github.com/gregseth/suncalc-php) and echoes JSON if successful

The script can also be run as a web-service with PHP's in-built webserver for testing the JSON request/responses.

## Features

- Runs on the command-line
- Can be called as a stand-alone webservice using the php command line built-in server
- Can specify a geo-location with latitude, longitude co-ordinates OR a city-id from GeoNames
- Provided with global city data (populations > 15000) from http://download.geonames.org/export/dump/ (cities15000.zip) listed/saved as JSON using --cities option, see also [data/cities15000.txt](data/cities15000.txt)
- When calling with a city-id, adds the city information to the 'meta' information returned in the JSON result
- Option to search for city information with --search-city
- All messages when running with `--debug` or `--verbose` are to *stderr* to avoid interference with *stdout*
- Can output the result if successful to *stdout*
- Errors are output in JSON as 'errors' with just a bunch of strings

```
{
    "errors": [
        "Unable to parse --date: next sunsaday"
    ]
}
```

## Returned results fields/columns/keys

- timestamp - timestamp specified in for data
- datestamp - human-readable date/time for timestamp
- sunrise_start - sunrise starts (top edge of the sun appears on the horizon)
- sunrise_end - sunrise ends (bottom edge of the sun appears on the horizon)
- dawn_nautical - nautical dawn (morning nautical twilight starts)
- dawn -  dawn (morning nautical twilight ends, morning civil twilight starts)
- golden_hour_morning -  morning golden hour (soft light, best time for photography) ends
- noon -  solar noon (sun is in the highest position)
- golden_hour_evening - evening golden hour starts
- dusk - dusk (evening nautical twilight starts)
- dusk_nautical - nautical dusk (evening astronomical twilight starts)
- sunset_start - sunset starts (bottom edge of the sun touches the horizon)
- sunset_end - sunset (sun disappears below the horizon, evening civil twilight starts)
- night - night starts (dark enough for astronomical observations)
- nadir - nadir (darkest moment of the night, sun is in the lowest position)
- night_end - night ends (morning astronomical twilight starts)

## Instructions

### Command-line options

```
Usage: php sun.php
Get the sun phase data using class https://github.com/gregseth/suncalc-php
(Specifying any other unknown argument options will be ignored.)

        -h,  --help                   Display this help and exit
        -v,  --verbose                Run in verbose mode
        -d,  --debug                  Run in debug mode (implies also -v, --verbose)
        -t,  --test                   Run in test mode, using co-ordinates for Skagen, Denmark from stormyglass.ini file by default.
        -e,  --echo                   (Optional) Echo/output the result to stdout if successful
        -r,  --refresh                (Optional) Force cache-refresh
             --search-city=<text>     Search for city using supplied text.
             --city-id={city_id}      (Optional) Specify GeoNames city id (in cities.json file) for required latitude/longitude values
             --latitude={-90 - 90}    (Required) Latitude (decimal degrees)
             --longitude={-180 - 180} (Required) Longitude (decimal degrees)
        -t   --date={now}             (Optional) Date/time default 'now' see: https://secure.php.net/manual/en/function.strtotime.php
             --dir=                   (Optional) Directory for storing files (sys_get_temp_dir() if not specified)
        -f,  --filename={output.}     (Optional) Filename for output data from operation
             --format={json}          (Optional) Output format for output filename (reserved for future): json (default)
```

### Requirements/Installation

- PHP7
- Create the 'cities.json' data file: `php sun.php --cities`

## City-search Example

Search for 'london' and show result in 'less' command-line text viewer with debugging enabled:

`php sun.php --search-city=london --echo --debug 2>&1 | less`

(Abbreviated result!)

```
[D 1/1] OPTIONS:
Array
(
    [cities] => 0
    [debug] => 1
    [echo] => 1
    [refresh] => 0
    [search-city] => 1
    [test] => 0
    [verbose] => 1
)
[V 1/1] OUTPUT_FORMAT: json
{
    "2643743": {
        "id": 2643743,
        "country_code": "GB",
        "state": "ENG",
        "city": "London",
        "ascii": "London",
        "names": [
            "Gorad Londan",
            "ILondon",
            "LON",
            "Lakana",
            "Landan",
        ],
        "latitude": 51.50853,
        "longitude": -0.12574,
        "elevation": 25,
        "population": null,
        "timezone": "Europe\/London"
    },
}[D 2/50] Memory used (2/50) MB (current/peak).
```

### Fetch data for London

This uses city-id instead of normally required latitude/longitude and will save results to file 'london.json' as well as output to screen 'stdout'

`php sun.php --city-id=2643743 --echo --filename='london.json'`

*Note:* Searching with city-id returns the city information too, otherwise providing only latitude/longitude will return sun data only.

```
{
    "sunrise_start": "1539584712",
    "dawn_nautical": "1539580374",
    "dawn": "1539582692",
    "golden_hour_morning": "1539587473",
    "noon": "1539604047",
    "golden_hour_evening": "1539620622",
    "dusk": "1539625403",
    "dusk_nautical": "1539627720",
    "sunset_start": "1539623172",
    "sunset_end": "1539623383",
    "night": "1539630046",
    "nadir": "1539560847",
    "night_end": "1539578049",
    "timestamp": 1540080000,
    "datestamp": "Sun, 21 Oct 2018 00:00:00 +0000"
    "city": {
        "id": 2643743,
        "country_code": "GB",
        "state": "ENG",
        "city": "London",
        "ascii": "London",
        "names": [
            "Gorad Londan",
            "ILondon",
            "LON",
            "Lakana",
            "Landan",
            "Landen",
            "Ljondan",
            "Llundain",
            "Lodoni",
            "Londain",
            "Londan",
            "Londar",
            "Londe",
            "Londen",
            "Londin",
            "Londinium",
            "Londino",
            "Londn",
            "London",
            "London osh",
            "Londona",
            "Londonas",
            "Londoni",
            "Londono",
            "Londons",
            "Londonu",
            "Londra",
            "Londres",
        ],
        "latitude": 51.50853,
        "longitude": -0.12574,
        "elevation": 25,
        "population": null,
        "timezone": "Europe\/London"
    }
}
```

### Save to file example

`php sun.php --date='next year' > test.json`

## Running as a webservice

### Starting the service

1. Start the PHP webserver with `php -S 127.0.0.1:12312`
2. Browse the URL: http://127.0.0.1:12312/sun.php with GET/POST parameters as 'date=<UNIX TIMESTAMP>'.

Accepted request input parameters: 'date=', 'latitude=', 'longitude=', 'city-id=', 'cities', 'refresh'

### Webservice Example

Search for sun phase 'next sunday' using geolocation co-ordinates for Paris:

http://127.0.0.1:12312/sun.php?latitude=48.85341&longitude=2.3488&date=next%20sunday

Result:

```
{
    "sunrise_start": "1540102855",
    "sunrise_end": "1540103057",
    "dawn_nautical": "1540098723",
    "dawn": "1540100925",
    "golden_hour_morning": "1540105502",
    "noon": "1540121784",
    "golden_hour_evening": "1540138067",
    "dusk": "1540142644",
    "dusk_nautical": "1540144846",
    "sunset_start": "1540140512",
    "sunset_end": "1540140714",
    "night": "1540147036",
    "nadir": "1540078584",
    "night_end": "1540096533",
    "timestamp": 1540080000,
    "datestamp": "Sun, 21 Oct 2018 00:00:00 +0000"
}
```

Using city-id:

`http://127.0.0.1:12312/sun.php?city-id=2988507&date=next%20sunday`

```
{
    "sunrise_start": "1540102855",
    "sunrise_end": "1540103057",
    "dawn_nautical": "1540098723",
    "dawn": "1540100925",
    "golden_hour_morning": "1540105502",
    "noon": "1540121784",
    "golden_hour_evening": "1540138067",
    "dusk": "1540142644",
    "dusk_nautical": "1540144846",
    "sunset_start": "1540140512",
    "sunset_end": "1540140714",
    "night": "1540147036",
    "nadir": "1540078584",
    "night_end": "1540096533",
    "timestamp": 1540080000,
    "datestamp": "Sun, 21 Oct 2018 00:00:00 +0000",
    "city": {
        "id": 2988507,
        "country_code": "FR",
        "state": 11,
        "city": "Paris",
        "ascii": "Paris",
        "names": [
            "Baariis",
            "Bahliz",
            "Gorad Paryzh",
            "Lungsod ng Paris",
            "Lutece",
            "Lutetia",
            "Lutetia Parisorum",
            "Lut\u00e8ce",
            "PAR",
            "Pa-ri",
            "Paarys",
            "Palika",
            "Paname",
            "Pantruche",
            "Paraeis",
            "Paras",
            "Pari",
            "Paries",
            "Parigge",
            "Pariggi",
            "Parighji",
            "Parigi",
            "Pariis",
            "Pariisi",
            "Pariizu",
            "Parii\u017eu",
            "Parij",
            "Parijs",
            "Paris",
            "Parisi",
            "Parixe",
            "Pariz",
            "Parize",
            "Parizh",
            "Parizh osh",
            "Parizh'",
            "Parizo",
            "Parizs",
            "Pari\u017e",
            "Parys",
            "Paryz",
            "Paryzius",
            "Pary\u017c",
            "Pary\u017eius",
            "Par\u00e4is",
            "Par\u00eds",
            "Par\u00ed\u017e",
            "Par\u00ees",
            "Par\u0129",
            "Par\u012b",
            "Par\u012bze",
            "Pa\u0159\u00ed\u017e",
            "P\u00e1ras",
            "P\u00e1rizs",
            "Ville-Lumiere",
            "Ville-Lumi\u00e8re",
            "ba li",
            "barys",
            "pairisa",
            "pali",
            "pari",
            "paris",
            "parys",
            "paryzh",
            "perisa",
            "pryz",
            "pyaris",
            "pyarisa",
            "pyrs",
        ],
        "latitude": 48.85341,
        "longitude": 2.3488,
        "elevation": 42,
        "population": null,
        "timezone": "Europe\/Paris"
    }
}
```

## Updating cities list

- Go to [http://download.geonames.org/export/dump/](http://download.geonames.org/export/dump/)
- Download [cities15000.zip](http://download.geonames.org/export/dump/cities15000.zip)
- Unzip and save to [data/cities.tsv](data/cities.tsv)
- Run script with '-r' to refresh cache and '--cities' to re-create cities.json


----
vijay@yoyo.org
