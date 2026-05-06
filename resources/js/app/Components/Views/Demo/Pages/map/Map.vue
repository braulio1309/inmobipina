<template>
    <div id="map-container" class="map"></div>
</template>

<script>
import { Loader } from '@googlemaps/js-api-loader';

export default {
    props: {
        token: {
            type: String,
            default: null
        },
        properties: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            loader: new Loader({
                apiKey: this.token,
                version: "weekly",
                libraries: ["places"]
            }),
            mapOptions: {
                center: { lat: 0, lng: 0 },
                zoom: 4,
            }
        }
    },
    methods: {
        getLocationPermission() {
            navigator.permissions.query({ name: 'geolocation' }).then((result) => {
                if (result.state === 'granted') {
                    console.log(granted)
                } else if (result.state === 'prompt') {
                    console.log('prompt')
                }
                // Don't do anything if the permission was denied.
            }).then(res => {
                console.log(res)
            })
        },
        setMarker(google, map, position, title) {
            const marker = new google.maps.Marker({
                position,
                map,
                title: title || ''
            });
            return marker;
        },
        setPropertyMarkers(google, map) {
            if (!this.properties || !this.properties.length) return;

            this.properties.forEach(property => {
                const lat = parseFloat(property.map_lat);
                const lng = parseFloat(property.map_lng);
                if (isNaN(lat) || isNaN(lng)) return;

                const marker = new google.maps.Marker({
                    position: { lat, lng },
                    map,
                    title: property.title || 'Propiedad',
                    icon: {
                        url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png'
                    }
                });

                const infoContent = `
                    <div style="max-width:200px">
                        <strong>${property.title || 'Propiedad'}</strong><br>
                        ${property.address ? '<span>' + property.address + '</span><br>' : ''}
                        ${property.price ? '<span>Precio: $' + property.price + '</span><br>' : ''}
                        ${property.status ? '<span>Estado: ' + property.status + '</span>' : ''}
                    </div>
                `;

                const infoWindow = new google.maps.InfoWindow({ content: infoContent });
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            });
        },
        getCurrentLocation(map, google) {
            let infoWindow = new google.maps.InfoWindow();

            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };

                        infoWindow.setPosition(pos);
                        infoWindow.open(map);
                        map.setCenter(pos);
                        map.setZoom(11);
                        infoWindow.setContent("Tu ubicación");
                        // set marker
                        this.setMarker(google, map, pos, 'Tu ubicación')
                    },
                    () => {
                        handleLocationError(true, infoWindow, map.getCenter());
                    }
                );

                function handleLocationError(browserHasGeolocation, infoWindow, pos) {
                    infoWindow.setPosition(pos);
                    infoWindow.setContent(
                        browserHasGeolocation
                            ? "Error: El servicio de geolocalización falló."
                            : "Error: Tu navegador no soporta geolocalización."
                    );
                    infoWindow.open(map);
                }
            } else {
                this.getLocationPermission()
                handleLocationError(false, infoWindow, map.getCenter());
            }
        },
        init() {
            this.loader
                .load()
                .then((google) => {
                    let map = new google.maps.Map(document.getElementById("map-container"), this.mapOptions);

                    // current location button
                    const locationButton = document.createElement("button");
                    locationButton.textContent = "Mi ubicación";
                    locationButton.classList.add("map--button");

                    map.controls[google.maps.ControlPosition.TOP_CENTER].push(locationButton);

                    locationButton.addEventListener("click", () => this.getCurrentLocation(map, google));

                    // Show property markers
                    this.setPropertyMarkers(google, map);

                    // Center map on first property with coordinates
                    if (this.properties && this.properties.length) {
                        const first = this.properties.find(p => p.map_lat && p.map_lng);
                        if (first) {
                            map.setCenter({ lat: parseFloat(first.map_lat), lng: parseFloat(first.map_lng) });
                            map.setZoom(12);
                        }
                    }
                })
                .catch(e => {
                    // do something
                });
        }
    },
    mounted() {
        this.init()
    }
}
</script>

<style lang="scss">
.map {
    min-height: 450px;
    height: 100%;

    &--button {
        border: none;
        background-color: rgba(0, 0, 0, 0.5);
        color: #fff;
        padding: 5px 15px;
        border-radius: 10px;
        margin-top: 20px;
        font-size: 16px;
        outline: none !important;

        &:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }
    }
}
</style>
