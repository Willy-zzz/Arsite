const shouldLog = import.meta.env.DEV || import.meta.env.VITE_ENABLE_CLIENT_LOGS === 'true'
const originalConsole = {
	log: console.log.bind(console),
	info: console.info.bind(console),
	warn: console.warn.bind(console),
	error: console.error.bind(console),
}

export const logger = {
	debug: (...args) => {
		if (shouldLog) originalConsole.log(...args)
	},
	info: (...args) => {
		if (shouldLog) originalConsole.info(...args)
	},
	warn: (...args) => {
		if (shouldLog) originalConsole.warn(...args)
	},
	error: (...args) => {
		if (shouldLog) originalConsole.error(...args)
	},
}

export const silenceBrowserConsole = () => {
	if (shouldLog) return

	console.log = () => {}
	console.info = () => {}
	console.warn = () => {}
	console.error = () => {}
}

export default logger
