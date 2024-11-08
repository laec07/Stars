﻿(function ($) {
    "use strict";
    var rtl = $('html[lang="zxx"]').attr('dir');
    $(document).ready(function () {
        $('.expertise-team').owlCarousel({
            loop: true,
            margin: 25,
            responsiveClass: true,
            autoplay: true,
            autoplayTimeout: 3000,
            rtl: rtl == "rtl" ? true : false,
            responsive: {
                0: {
                    items: 1
                },
                480: {
                    items: 1
                },
                1000: {
                    items: 4
                }
            }
        });

        $('.new-team').owlCarousel({
            loop: true,
            margin: 25,
            responsiveClass: true,
            autoplay: true,
            autoplayTimeout: 3000,
            rtl: rtl == "rtl" ? true : false,
            responsive: {
                0: {
                    items: 1
                },
                480: {
                    items: 1
                },
                1000: {
                    items: 4
                }
            }
        });

        $('.top-client-testimonial').owlCarousel({
            loop: true,
            margin: 25,
            responsiveClass: true,
            autoplay: true,
            autoplayTimeout: 4000,
            rtl: rtl == "rtl" ? true : false,
            responsive: {
                0: {
                    items: 1
                },
                480: {
                    items: 1
                },
                1000: {
                    items: 2
                }
            }
        });

        $('.client-say-about-us').owlCarousel({
            loop: true,
            margin: 25,
            responsiveClass: true,
            autoplay: true,
            autoplayTimeout: 4000,
            rtl: rtl == "rtl" ? true : false,
            responsive: {
                0: {
                    items: 1
                },
                480: {
                    items: 1
                },
                1000: {
                    items: 1
                }
            }
        });

        $('.img-gal').magnificPopup({
            type: 'image',
            gallery: {
                enabled: true
            }
        });

        if (document.getElementById("map")) {
            var lat = $("#maplat").val();
            var long = $("#maplong").val();
            google.maps.event.addDomListener(window, 'load', init);

            function init() {
                var mapOptions = {
                    zoom: 11,
                    center: new google.maps.LatLng(lat, long),
                    styles: [{
                        "featureType": "water",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#e9e9e9"
                        }, {
                            "lightness": 17
                        }]
                    }, {
                        "featureType": "landscape",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#f5f5f5"
                        }, {
                            "lightness": 20
                        }]
                    }, {
                        "featureType": "road.highway",
                        "elementType": "geometry.fill",
                        "stylers": [{
                            "color": "#ffffff"
                        }, {
                            "lightness": 17
                        }]
                    }, {
                        "featureType": "road.highway",
                        "elementType": "geometry.stroke",
                        "stylers": [{
                            "color": "#ffffff"
                        }, {
                            "lightness": 29
                        }, {
                            "weight": 0.2
                        }]
                    }, {
                        "featureType": "road.arterial",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#ffffff"
                        }, {
                            "lightness": 18
                        }]
                    }, {
                        "featureType": "road.local",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#ffffff"
                        }, {
                            "lightness": 16
                        }]
                    }, {
                        "featureType": "poi",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#f5f5f5"
                        }, {
                            "lightness": 21
                        }]
                    }, {
                        "featureType": "poi.park",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#dedede"
                        }, {
                            "lightness": 21
                        }]
                    }, {
                        "elementType": "labels.text.stroke",
                        "stylers": [{
                            "visibility": "on"
                        }, {
                            "color": "#ffffff"
                        }, {
                            "lightness": 16
                        }]
                    }, {
                        "elementType": "labels.text.fill",
                        "stylers": [{
                            "saturation": 36
                        }, {
                            "color": "#333333"
                        }, {
                            "lightness": 40
                        }]
                    }, {
                        "elementType": "labels.icon",
                        "stylers": [{
                            "visibility": "off"
                        }]
                    }, {
                        "featureType": "transit",
                        "elementType": "geometry",
                        "stylers": [{
                            "color": "#f2f2f2"
                        }, {
                            "lightness": 19
                        }]
                    }, {
                        "featureType": "administrative",
                        "elementType": "geometry.fill",
                        "stylers": [{
                            "color": "#fefefe"
                        }, {
                            "lightness": 20
                        }]
                    }, {
                        "featureType": "administrative",
                        "elementType": "geometry.stroke",
                        "stylers": [{
                            "color": "#fefefe"
                        }, {
                            "lightness": 17
                        }, {
                            "weight": 1.2
                        }]
                    }]
                };
                var mapElement = document.getElementById('map');
                var map = new google.maps.Map(mapElement, mapOptions);
                var marker = new google.maps.Marker({
                    position: new google.maps.LatLng(lat, long),
                    map: map,
                    title: 'Snazzy!'
                });
            }
        }
    });
})(jQuery);