import global from "./global";
import helper from "./helper";

let publicJs = {
    init() {

        let swiper = new Swiper(".mySwiper", {
            spaceBetween: 0,
            speed: 2000,
            effect: "fade",
            centeredSlides: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
                disabledClass: "disabled_swiper_button",
            },
        });

        swiper.on("slideChange", () => {
            const slideIndex = swiper.activeIndex;
            const previous = parseInt(slideIndex) - 1;
            const lastIndex = parseInt(swiper.slides.length) - 1;

            let i, j;

            if (slideIndex > 0) {
                setTimeout(() => {
                    for (i = 0; i <= previous; i++) {
                        $(`#slideIndex${i}`).hide();
                    }

                    for (j = slideIndex + 1; j <= lastIndex; j++) {
                        $(`#slideIndex${j}`).hide();
                    }

                    $(`#slideIndex${slideIndex}`).show();
                }, 300);
            } else {
                $(`#slideIndex0`).show();
                for (i = 1; i <= lastIndex; i++) {
                    $(`#slideIndex${i}`).hide();
                }
            }
        });

        let testimonialSwiper = new Swiper(".testimonial-swiper", {
            spaceBetween: 0,
            speed: 600,
            effect: "flip",
            flipEffect: {
                slideShadows: false,
            },
            centeredSlides: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: true,
            },
        });
        $(".prev-arrow").on('click', e => {
            console.log("prev");
            testimonialSwiper.slidePrev();
        });
        $(".next-arrow").on('click', e => {
            console.log("next");
            testimonialSwiper.slideNext();
        });

        this.initHandlers();
        this.initMap();
    },

    initHandlers() {
        $("form").on("submit", (e) => {
            self = $(e.target);

            self.children()
                .find('button[type="submit"]')
                .prop("disabled", true);
        });
    },

    googleKey: global.googleKey,
    initMap() {
        window.initMap = this.startZonesMap;
        const src = `https://maps.googleapis.com/maps/api/js?key=${publicJs.googleKey}&callback=window.initMap&libraries=drawing,geometry,places&v=weekly`;
        $("body").append(
            window.e("script", {
                src,
            })
        );
    },

    zones_color: [],
    zones_on_map: [],
    startZonesMap() {
        // Initialize and add the map
        // The location of defaultLocation
        const defaultLocation = {
            lat: 43.6156598,
            lng: -116.4865859,
        };
        window.defaultLocation = defaultLocation;
        // The map, centered at defaultLocation
        const map = new google.maps.Map(document.getElementById("zones-map"), {
            zoom: 9,
            center: defaultLocation,
        });
        let dm = new google.maps.drawing.DrawingManager({
            drawingMode: false,
            drawingControl: false,
            polygonOptions: {
                editable: false,
                clickable: false,
            },
        });
        dm.setMap(map);
        window.dm = dm;

        //after click [add area] and finish draw  store the shape
        // publicJs.onZoneCreated();

        //go to server and load zones from db
        //render zones in map
        publicJs.loadZonesOnMap(map);

        window.map = map;
        window.google = google;
    },

    loadZonesOnMap(map) {
        $.get("/locations-served/zones").done((zones) => {
            zones.forEach((zone) => {
                let color = zone.color;
    
                let points = publicJs.parseZonePoints(zone.points);

                let overlay = new google.maps.Polygon({
                    paths: points,
                    fillColor: color,
                    strokeColor: color,
                    editable: false,
                    clickable: false,
                });

                overlay.setMap(map);
                overlay.zone_id = zone.id;
            });
        });
    },

    parseZonePoints(points) {
        points = JSON.parse(points);
        return points.map((p) => {
            return {
                lat: parseFloat(p.lat),
                lng: parseFloat(p.lng),
            };
        });
    },

};

$(() => {
    publicJs.init();
});
