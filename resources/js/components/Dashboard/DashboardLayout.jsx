import React, { useState } from 'react';
import { Link, Outlet, useLocation } from 'react-router-dom';
import LanguageSwitcher from '../LanguageSwitcher';

export default function DashboardLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const location = useLocation();
    const isRTL = localStorage.getItem('locale') === 'ar';

    const navigation = [
        { name: 'Dashboard', href: '/dashboard', icon: '📊' },
        { name: 'Boats', href: '/dashboard/boats', icon: '🚤' },
        { name: 'Equipment', href: '/dashboard/equipment', icon: '🎣' },
        { name: 'Bookings', href: '/dashboard/bookings', icon: '📅' },
        { name: 'Orders', href: '/dashboard/orders', icon: '📦' },
        { name: 'Calendar', href: '/dashboard/calendar', icon: '🗓️' },
        { name: 'Earnings', href: '/dashboard/earnings', icon: '💰' },
        { name: 'Messages', href: '/dashboard/messages', icon: '💬' },
        { name: 'Verification', href: '/dashboard/verification', icon: '✅' },
    ];

    return (
        <div className={`min-h-screen bg-gray-50 ${isRTL ? 'rtl' : 'ltr'}`} dir={isRTL ? 'rtl' : 'ltr'}>
            {/* Mobile sidebar */}
            <div className={`fixed inset-0 z-50 lg:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)}></div>
                <div className={`fixed inset-y-0 ${isRTL ? 'right-0' : 'left-0'} w-64 bg-white shadow-xl`}>
                    <SidebarContent navigation={navigation} location={location} onClose={() => setSidebarOpen(false)} />
                </div>
            </div>

            {/* Desktop sidebar */}
            <div className="hidden lg:flex lg:flex-shrink-0">
                <div className={`flex flex-col w-64 ${isRTL ? 'border-l' : 'border-r'} border-gray-200 bg-white`}>
                    <SidebarContent navigation={navigation} location={location} />
                </div>
            </div>

            {/* Main content */}
            <div className={`flex flex-col flex-1 ${isRTL ? 'lg:mr-64' : 'lg:ml-64'}`}>
                {/* Top bar */}
                <div className="sticky top-0 z-10 bg-white shadow-sm">
                    <div className="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden text-gray-500 hover:text-gray-700"
                        >
                            <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div className="flex items-center space-x-4">
                            <LanguageSwitcher />
                            <Link to="/" className="text-gray-500 hover:text-gray-700">
                                ← Back to Site
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="flex-1 p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}

function SidebarContent({ navigation, location, onClose }) {
    return (
        <div className="flex flex-col h-full">
            <div className="flex items-center h-16 px-4 border-b border-gray-200">
                <span className="text-xl font-bold text-blue-600">iBoat Dashboard</span>
            </div>
            <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                {navigation.map((item) => {
                    const isActive = location.pathname === item.href || location.pathname.startsWith(item.href + '/');
                    return (
                        <Link
                            key={item.name}
                            to={item.href}
                            onClick={onClose}
                            className={`flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                isActive
                                    ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700'
                                    : 'text-gray-700 hover:bg-gray-50'
                            }`}
                        >
                            <span className="mr-3 text-lg">{item.icon}</span>
                            {item.name}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}

