/** @type {import('tailwindcss').Config} */
export default {
	content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
	theme: {
		extend: {
			fontFamily: {
				// Esto le dice a Tailwind que la fuente 'sans' principal es Inter
				sans: ['Inter', 'sans-serif'],
			},
		},
	},
	plugins: [],
}
