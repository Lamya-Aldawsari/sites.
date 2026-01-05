import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function DashboardHome() {
    const [dashboard, setDashboard] = useState(null);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        fetchDashboard();
    }, []);

    const fetchDashboard = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setDashboard(response.data);
        } catch (error) {
            console.error('Error fetching dashboard:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading dashboard...</div>;
    }

    if (!dashboard) {
        return <div className="text-center py-12">Failed to load dashboard</div>;
    }

    const isCaptain = dashboard.role === 'captain';
    const stats = dashboard.stats;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
                    {isCaptain ? 'Captain Dashboard' : 'Vendor Dashboard'}
                </h1>
                <p className="mt-2 text-gray-600">Welcome back! Here's your overview.</p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <StatCard
                    title={isCaptain ? 'Total Boats' : 'Total Equipment'}
                    value={isCaptain ? stats.total_boats : stats.total_equipment}
                    icon="📊"
                    onClick={() => navigate(isCaptain ? '/dashboard/boats' : '/dashboard/equipment')}
                />
                <StatCard
                    title={isCaptain ? 'Active Bookings' : 'Pending Orders'}
                    value={isCaptain ? stats.active_bookings : stats.pending_orders}
                    icon="📅"
                    onClick={() => navigate('/dashboard/bookings')}
                />
                <StatCard
                    title="Total Earnings"
                    value={`$${stats.total_earnings?.toFixed(2) || '0.00'}`}
                    icon="💰"
                    onClick={() => navigate('/dashboard/earnings')}
                />
                <StatCard
                    title="Unread Messages"
                    value={dashboard.unread_messages || 0}
                    icon="💬"
                    onClick={() => navigate('/dashboard/messages')}
                    highlight={dashboard.unread_messages > 0}
                />
            </div>

            {/* Verification Status */}
            {dashboard.verification_status && (
                <VerificationCard status={dashboard.verification_status} />
            )}

            {/* Recent Activity */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {isCaptain ? (
                    <>
                        <RecentBookings bookings={dashboard.recent_bookings} />
                        <RecentEarnings earnings={dashboard.recent_earnings} />
                    </>
                ) : (
                    <>
                        <RecentOrders orders={dashboard.recent_orders} />
                        <RecentEarnings earnings={dashboard.recent_earnings} />
                    </>
                )}
            </div>
        </div>
    );
}

function StatCard({ title, value, icon, onClick, highlight }) {
    return (
        <div
            onClick={onClick}
            className={`bg-white rounded-lg shadow p-4 sm:p-6 cursor-pointer hover:shadow-md transition-shadow ${
                highlight ? 'ring-2 ring-blue-500' : ''
            }`}
        >
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-600">{title}</p>
                    <p className={`text-2xl sm:text-3xl font-bold mt-2 ${highlight ? 'text-blue-600' : 'text-gray-900'}`}>
                        {value}
                    </p>
                </div>
                <span className="text-3xl sm:text-4xl">{icon}</span>
            </div>
        </div>
    );
}

function VerificationCard({ status }) {
    const progress = (status.approved_count / status.required_documents.length) * 100;

    return (
        <div className="bg-white rounded-lg shadow p-4 sm:p-6">
            <h3 className="text-lg font-semibold mb-4">Verification Status</h3>
            <div className="mb-4">
                <div className="flex justify-between text-sm mb-2">
                    <span>Progress</span>
                    <span>{status.approved_count} / {status.required_documents.length} approved</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                        className={`h-2 rounded-full transition-all ${
                            status.is_verified ? 'bg-green-500' : 'bg-blue-500'
                        }`}
                        style={{ width: `${progress}%` }}
                    ></div>
                </div>
            </div>
            <div className="space-y-2">
                {status.required_documents.map((docType) => {
                    const doc = status.uploaded_documents[docType];
                    return (
                        <div key={docType} className="flex items-center justify-between text-sm">
                            <span className="capitalize">{docType.replace('_', ' ')}</span>
                            <span className={`px-2 py-1 rounded text-xs ${
                                doc.status === 'approved' ? 'bg-green-100 text-green-800' :
                                doc.status === 'rejected' ? 'bg-red-100 text-red-800' :
                                doc.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {doc.status === 'not_uploaded' ? 'Not Uploaded' : doc.status}
                            </span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function RecentBookings({ bookings }) {
    return (
        <div className="bg-white rounded-lg shadow p-4 sm:p-6">
            <h3 className="text-lg font-semibold mb-4">Recent Bookings</h3>
            <div className="space-y-3">
                {bookings?.slice(0, 5).map((booking) => (
                    <div key={booking.id} className="border-b pb-3 last:border-0">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="font-medium">{booking.customer?.name}</p>
                                <p className="text-sm text-gray-600">{booking.boat?.name}</p>
                                <p className="text-xs text-gray-500">
                                    {new Date(booking.start_time).toLocaleDateString()}
                                </p>
                            </div>
                            <span className={`px-2 py-1 rounded text-xs ${
                                booking.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {booking.status}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function RecentOrders({ orders }) {
    return (
        <div className="bg-white rounded-lg shadow p-4 sm:p-6">
            <h3 className="text-lg font-semibold mb-4">Recent Orders</h3>
            <div className="space-y-3">
                {orders?.slice(0, 5).map((order) => (
                    <div key={order.id} className="border-b pb-3 last:border-0">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="font-medium">Order #{order.order_number}</p>
                                <p className="text-sm text-gray-600">{order.customer?.name}</p>
                                <p className="text-xs text-gray-500">
                                    {new Date(order.created_at).toLocaleDateString()}
                                </p>
                            </div>
                            <span className={`px-2 py-1 rounded text-xs ${
                                order.status === 'delivered' ? 'bg-green-100 text-green-800' :
                                order.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {order.status}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function RecentEarnings({ earnings }) {
    return (
        <div className="bg-white rounded-lg shadow p-4 sm:p-6">
            <h3 className="text-lg font-semibold mb-4">Recent Earnings</h3>
            <div className="space-y-3">
                {earnings?.slice(0, 5).map((earning) => (
                    <div key={earning.id} className="border-b pb-3 last:border-0">
                        <div className="flex justify-between items-center">
                            <div>
                                <p className="text-sm text-gray-600">
                                    {new Date(earning.processed_at).toLocaleDateString()}
                                </p>
                            </div>
                            <p className="font-semibold text-green-600">
                                ${(earning.captain_amount || earning.vendor_amount)?.toFixed(2)}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

