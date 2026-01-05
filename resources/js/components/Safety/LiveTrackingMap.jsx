import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';

export default function LiveTrackingMap({ tripLogId, bookingId, height = '500px' }) {
    const [locations, setLocations] = useState([]);
    const [currentLocation, setCurrentLocation] = useState(null);
    const [map, setMap] = useState(null);
    const [marker, setMarker] = useState(null);
    const [routePolyline, setRoutePolyline] = useState(null);
    const mapRef = useRef(null);
    const [mapLoaded, setMapLoaded] = useState(false);

    useEffect(() => {
        // Load Google Maps
        if (!window.google) {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${process.env.REACT_APP_GOOGLE_MAPS_API_KEY || 'YOUR_API_KEY'}&libraries=geometry`;
            script.async = true;
            script.defer = true;
            script.onload = initializeMap;
            document.head.appendChild(script);
        } else {
            initializeMap();
        }

        return () => {
            if (marker) marker.setMap(null);
            if (routePolyline) routePolyline.setMap(null);
        };
    }, []);

    useEffect(() => {
        if (map && tripLogId) {
            fetchRoute();
            startLocationUpdates();
        }
    }, [map, tripLogId]);

    useEffect(() => {
        if (map && currentLocation) {
            updateMapMarker();
            updateRoute();
        }
    }, [currentLocation, map]);

    const initializeMap = () => {
        if (!mapRef.current) return;

        const googleMap = new window.google.maps.Map(mapRef.current, {
            zoom: 12,
            center: { lat: 25.7907, lng: -80.1300 }, // Default to Miami
            mapTypeId: 'hybrid',
        });

        setMap(googleMap);
        setMapLoaded(true);
    };

    const fetchRoute = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get(`/api/trips/${tripLogId}/route`, {
                headers: { Authorization: `Bearer ${token}` },
            });

            if (response.data.route && response.data.route.length > 0) {
                setLocations(response.data.route);
                
                // Center map on first location
                if (map && response.data.route[0]) {
                    map.setCenter({
                        lat: response.data.route[0].lat,
                        lng: response.data.route[0].lng,
                    });
                }
            }
        } catch (error) {
            console.error('Error fetching route:', error);
        }
    };

    const startLocationUpdates = () => {
        // Fetch current location every 5 seconds
        const interval = setInterval(async () => {
            try {
                const token = localStorage.getItem('token');
                const response = await axios.get(`/api/trips/${tripLogId}/current-location`, {
                    headers: { Authorization: `Bearer ${token}` },
                });

                if (response.data) {
                    setCurrentLocation({
                        lat: parseFloat(response.data.latitude),
                        lng: parseFloat(response.data.longitude),
                        speed: response.data.speed_knots,
                        heading: response.data.heading_degrees,
                        timestamp: response.data.recorded_at,
                    });
                }
            } catch (error) {
                console.error('Error fetching location:', error);
            }
        }, 5000);

        return () => clearInterval(interval);
    };

    const updateMapMarker = () => {
        if (!map || !currentLocation) return;

        // Remove existing marker
        if (marker) {
            marker.setMap(null);
        }

        // Create new marker
        const newMarker = new window.google.maps.Marker({
            position: { lat: currentLocation.lat, lng: currentLocation.lng },
            map: map,
            icon: {
                path: window.google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                scale: 5,
                rotation: currentLocation.heading || 0,
                fillColor: '#FF0000',
                fillOpacity: 1,
                strokeColor: '#FFFFFF',
                strokeWeight: 2,
            },
            title: `Speed: ${currentLocation.speed || 0} knots`,
        });

        setMarker(newMarker);
        map.setCenter({ lat: currentLocation.lat, lng: currentLocation.lng });
    };

    const updateRoute = () => {
        if (!map || !currentLocation) return;

        // Add current location to route
        const updatedLocations = [...locations, {
            lat: currentLocation.lat,
            lng: currentLocation.lng,
            timestamp: currentLocation.timestamp,
        }];

        setLocations(updatedLocations);

        // Remove existing polyline
        if (routePolyline) {
            routePolyline.setMap(null);
        }

        // Create route polyline
        if (updatedLocations.length > 1) {
            const path = updatedLocations.map(loc => ({
                lat: loc.lat,
                lng: loc.lng,
            }));

            const polyline = new window.google.maps.Polyline({
                path: path,
                geodesic: true,
                strokeColor: '#FF0000',
                strokeOpacity: 1.0,
                strokeWeight: 3,
                map: map,
            });

            setRoutePolyline(polyline);
        }
    };

    if (!mapLoaded) {
        return (
            <div className="flex items-center justify-center bg-gray-100 rounded-lg" style={{ height }}>
                <p>Loading map...</p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {currentLocation && (
                <div className="bg-white rounded-lg shadow p-4">
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p className="text-gray-600">Speed</p>
                            <p className="font-semibold">{currentLocation.speed?.toFixed(1) || 0} knots</p>
                        </div>
                        <div>
                            <p className="text-gray-600">Heading</p>
                            <p className="font-semibold">{currentLocation.heading?.toFixed(0) || 0}°</p>
                        </div>
                        <div>
                            <p className="text-gray-600">Latitude</p>
                            <p className="font-semibold">{currentLocation.lat.toFixed(6)}</p>
                        </div>
                        <div>
                            <p className="text-gray-600">Longitude</p>
                            <p className="font-semibold">{currentLocation.lng.toFixed(6)}</p>
                        </div>
                    </div>
                </div>
            )}
            <div ref={mapRef} className="w-full rounded-lg shadow" style={{ height }}></div>
        </div>
    );
}

