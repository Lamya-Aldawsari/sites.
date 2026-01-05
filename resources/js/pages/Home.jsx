import React from 'react';
import { Link } from 'react-router-dom';

export default function Home() {
    return (
        <div className="relative bg-white overflow-hidden">
            <div className="max-w-7xl mx-auto">
                <div className="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                    <main className="mt-6 sm:mt-10 mx-auto max-w-7xl px-4 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                        <div className="text-center sm:text-left">
                            <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl tracking-tight font-extrabold text-gray-900">
                                <span className="block xl:inline">Marine Transport &</span>{' '}
                                <span className="block text-blue-600 xl:inline">Rental Platform</span>
                            </h1>
                            <p className="mt-3 text-sm sm:text-base md:text-lg lg:text-xl text-gray-500 sm:mt-5 sm:max-w-xl sm:mx-auto md:mt-5 lg:mx-0">
                                Connect with boat owners, rent marine equipment, and experience the ocean like never before. 
                                Your Uber/Airbnb for boats.
                            </p>
                            <div className="mt-5 sm:mt-8 flex flex-col sm:flex-row sm:justify-center lg:justify-start gap-3 sm:gap-0">
                                <div className="rounded-md shadow">
                                    <Link to="/boats" className="w-full flex items-center justify-center px-6 sm:px-8 py-2 sm:py-3 border border-transparent text-sm sm:text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                                        Book a Boat
                                    </Link>
                                </div>
                                <div className="sm:ml-3">
                                    <Link to="/on-demand" className="w-full flex items-center justify-center px-6 sm:px-8 py-2 sm:py-3 border border-transparent text-sm sm:text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 md:py-4 md:text-lg md:px-10">
                                        On-Demand Ride
                                    </Link>
                                </div>
                                <div className="sm:ml-3">
                                    <Link to="/equipment" className="w-full flex items-center justify-center px-6 sm:px-8 py-2 sm:py-3 border border-transparent text-sm sm:text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10">
                                        Shop Equipment
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}

