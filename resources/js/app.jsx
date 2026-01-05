import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import './bootstrap';
import Layout from './components/Layout';
import Home from './pages/Home';
import Boats from './pages/Boats';
import BoatDetail from './pages/BoatDetail';
import Equipment from './pages/Equipment';
import OnDemandBooking from './pages/OnDemandBooking';
import Cart from './pages/Cart';
import Checkout from './pages/Checkout';
import VendorDashboard from './pages/VendorDashboard';
import Verification from './pages/Verification';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import DashboardLayout from './components/Dashboard/DashboardLayout';
import DashboardHome from './pages/Dashboard/DashboardHome';
import BoatsManagement from './pages/Dashboard/BoatsManagement';
import CalendarView from './pages/Dashboard/CalendarView';
import BookingsControl from './pages/Dashboard/BookingsControl';
import EarningsView from './pages/Dashboard/EarningsView';
import MessagesView from './pages/Dashboard/MessagesView';
import VerificationStatus from './pages/Dashboard/VerificationStatus';
import TripTracking from './pages/Dashboard/TripTracking';

function App() {
    return (
        <Router>
            <Layout>
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/boats" element={<Boats />} />
                    <Route path="/boats/:id" element={<BoatDetail />} />
                    <Route path="/equipment" element={<Equipment />} />
                    <Route path="/on-demand" element={<OnDemandBooking />} />
                    <Route path="/cart" element={<Cart />} />
                    <Route path="/checkout" element={<Checkout />} />
                    <Route path="/vendor/dashboard" element={<VendorDashboard />} />
                    <Route path="/verification" element={<Verification />} />
                    <Route path="/login" element={<Login />} />
                    <Route path="/register" element={<Register />} />
                    <Route path="/dashboard" element={<DashboardLayout />}>
                        <Route index element={<DashboardHome />} />
                        <Route path="boats" element={<BoatsManagement />} />
                        <Route path="equipment" element={<VendorDashboard />} />
                        <Route path="bookings" element={<BookingsControl />} />
                        <Route path="orders" element={<VendorDashboard />} />
                        <Route path="calendar" element={<CalendarView />} />
                        <Route path="earnings" element={<EarningsView />} />
                        <Route path="messages" element={<MessagesView />} />
                        <Route path="verification" element={<VerificationStatus />} />
                        <Route path="trips/:bookingId" element={<TripTracking />} />
                    </Route>
                </Routes>
            </Layout>
        </Router>
    );
}

ReactDOM.createRoot(document.getElementById('app')).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
