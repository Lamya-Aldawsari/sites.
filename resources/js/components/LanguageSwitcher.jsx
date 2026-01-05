import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function LanguageSwitcher() {
    const [locale, setLocale] = useState('en');

    useEffect(() => {
        // Get current locale from session or default
        const savedLocale = localStorage.getItem('locale') || 'en';
        setLocale(savedLocale);
        updateLocale(savedLocale);
    }, []);

    const updateLocale = async (newLocale) => {
        try {
            const token = localStorage.getItem('token');
            await axios.post(
                '/api/locale',
                { locale: newLocale },
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );
            localStorage.setItem('locale', newLocale);
            window.location.reload(); // Reload to apply translations
        } catch (error) {
            // Fallback: just update localStorage
            localStorage.setItem('locale', newLocale);
            window.location.reload();
        }
    };

    const handleChange = (e) => {
        const newLocale = e.target.value;
        setLocale(newLocale);
        updateLocale(newLocale);
    };

    return (
        <select
            value={locale}
            onChange={handleChange}
            className="border rounded-md px-2 py-1 text-sm"
        >
            <option value="en">English</option>
            <option value="ar">العربية</option>
        </select>
    );
}

