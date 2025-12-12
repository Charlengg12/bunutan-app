/**
 * Supabase Client Configuration
 * Modern minimalist setup for Bunutan Gift Exchange
 */

// Load Supabase from CDN
const SUPABASE_URL = import.meta.env?.VITE_SUPABASE_URL || window.ENV?.SUPABASE_URL || '';
const SUPABASE_ANON_KEY = import.meta.env?.VITE_SUPABASE_ANON_KEY || window.ENV?.SUPABASE_ANON_KEY || '';

// Initialize Supabase client
let supabase = null;

// Initialize function
async function initSupabase() {
    if (supabase) return supabase;

    // Load Supabase library if not already loaded
    if (typeof window.supabase === 'undefined') {
        await loadSupabaseLibrary();
    }

    supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
    return supabase;
}

// Load Supabase library dynamically
function loadSupabaseLibrary() {
    return new Promise((resolve, reject) => {
        if (typeof window.supabase !== 'undefined') {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2';
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

// API Helper Functions
const SupabaseAPI = {
    // Participants
    async addParticipant(name, email = null) {
        const sb = await initSupabase();
        const { data, error } = await sb
            .from('participants')
            .insert([{ name, email, added_at: new Date().toISOString() }])
            .select()
            .single();

        if (error) throw error;
        return data;
    },

    async getParticipants() {
        const sb = await initSupabase();
        const { data, error } = await sb
            .from('participants')
            .select('*')
            .order('added_at', { ascending: true });

        if (error) throw error;
        return data || [];
    },

    async deleteParticipant(id) {
        const sb = await initSupabase();

        // Check if draw is generated
        const drawGenerated = await this.getSetting('draw_generated');
        if (drawGenerated === 'true') {
            throw new Error('Cannot delete participants after draw is generated');
        }

        const { error } = await sb
            .from('participants')
            .delete()
            .eq('id', id);

        if (error) throw error;
        return true;
    },

    async bulkAddParticipants(names) {
        const sb = await initSupabase();

        // Check if draw is generated
        const drawGenerated = await this.getSetting('draw_generated');
        if (drawGenerated === 'true') {
            throw new Error('Cannot add participants after draw is generated');
        }

        const participants = names.map(name => ({
            name,
            added_at: new Date().toISOString()
        }));

        const { data, error } = await sb
            .from('participants')
            .insert(participants)
            .select();

        if (error) {
            // Handle duplicate names gracefully
            if (error.code === '23505') { // unique violation
                throw new Error('One or more participant names already exist');
            }
            throw error;
        }

        return data || [];
    },

    // Settings
    async getSetting(key) {
        const sb = await initSupabase();
        const { data, error } = await sb
            .from('settings')
            .select('value')
            .eq('key', key)
            .maybeSingle();

        if (error) throw error;
        return data?.value || '';
    },

    async getSettings() {
        const sb = await initSupabase();
        const { data, error } = await sb
            .from('settings')
            .select('*');

        if (error) throw error;

        // Convert array to object
        const settings = {};
        data.forEach(item => {
            settings[item.key] = item.value;
        });

        return settings;
    },

    async setSetting(key, value) {
        const sb = await initSupabase();
        const { error } = await sb
            .from('settings')
            .upsert({ key, value }, { onConflict: 'key' });

        if (error) throw error;
        return true;
    },

    async setGiftRules(rules) {
        return await this.setSetting('gift_value_rules', rules);
    },

    // Draws
    async generateDraw() {
        const sb = await initSupabase();

        // Get all participants
        const participants = await this.getParticipants();

        if (participants.length < 2) {
            throw new Error('At least 2 participants are required');
        }

        // Check if draw already generated
        const drawGenerated = await this.getSetting('draw_generated');
        if (drawGenerated === 'true') {
            throw new Error('Draw already generated');
        }

        // Generate derangement (no self-assignments)
        const givers = [...participants];
        const receivers = [...participants];
        let validDraw = false;
        let attempts = 0;
        const maxAttempts = 1000;

        while (!validDraw && attempts < maxAttempts) {
            // Shuffle receivers
            for (let i = receivers.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [receivers[i], receivers[j]] = [receivers[j], receivers[i]];
            }

            // Check if anyone got themselves
            validDraw = true;
            for (let i = 0; i < givers.length; i++) {
                if (givers[i].id === receivers[i].id) {
                    validDraw = false;
                    break;
                }
            }

            attempts++;
        }

        if (!validDraw) {
            throw new Error('Failed to generate valid draw');
        }

        // Create draw records with unique tokens
        const draws = givers.map((giver, index) => ({
            giver_id: giver.id,
            receiver_id: receivers[index].id,
            token: generateToken(),
            revealed: false,
            revealed_at: null
        }));

        // Insert draws
        const { data, error } = await sb
            .from('draws')
            .insert(draws)
            .select(`
                *,
                giver:participants!draws_giver_id_fkey(name),
                receiver:participants!draws_receiver_id_fkey(name)
            `);

        if (error) throw error;

        // Update settings
        await this.setSetting('draw_generated', 'true');
        await this.setSetting('draw_date', new Date().toISOString());

        // Format response to match expected structure
        const formattedDraws = data.map(draw => ({
            id: draw.id,
            giver_id: draw.giver_id,
            giver_name: draw.giver.name,
            receiver_id: draw.receiver_id,
            receiver_name: draw.receiver.name,
            token: draw.token,
            revealed: draw.revealed,
            revealed_at: draw.revealed_at
        }));

        return formattedDraws;
    },

    async getDraw() {
        const sb = await initSupabase();
        const { data, error } = await sb
            .from('draws')
            .select(`
                *,
                giver:participants!draws_giver_id_fkey(name),
                receiver:participants!draws_receiver_id_fkey(name)
            `)
            .order('created_at', { ascending: true });

        if (error) throw error;

        // Format response
        return (data || []).map(draw => ({
            id: draw.id,
            giver_id: draw.giver_id,
            giver_name: draw.giver.name,
            receiver_id: draw.receiver_id,
            receiver_name: draw.receiver.name,
            token: draw.token,
            revealed: draw.revealed,
            revealed_at: draw.revealed_at
        }));
    },

    async revealPartner(token) {
        const sb = await initSupabase();

        // Find draw by token
        const { data: draw, error: fetchError } = await sb
            .from('draws')
            .select(`
                *,
                giver:participants!draws_giver_id_fkey(name),
                receiver:participants!draws_receiver_id_fkey(name)
            `)
            .eq('token', token)
            .maybeSingle();

        if (fetchError) throw fetchError;
        if (!draw) throw new Error('Invalid or expired token');

        // Mark as revealed if not already
        if (!draw.revealed) {
            const { error: updateError } = await sb
                .from('draws')
                .update({
                    revealed: true,
                    revealed_at: new Date().toISOString()
                })
                .eq('id', draw.id);

            if (updateError) throw updateError;
        }

        // Get gift rules
        const giftRules = await this.getSetting('gift_value_rules');

        return {
            giver_name: draw.giver.name,
            receiver_name: draw.receiver.name,
            gift_rules: giftRules,
            revealed_at: draw.revealed_at || new Date().toISOString()
        };
    },

    // Statistics
    async getStatistics() {
        const sb = await initSupabase();

        const [participants, draws, settings] = await Promise.all([
            this.getParticipants(),
            this.getDraw(),
            this.getSettings()
        ]);

        const revealedCount = draws.filter(d => d.revealed).length;
        const totalDraws = draws.length;

        return {
            total_participants: participants.length,
            draw_generated: settings.draw_generated === 'true',
            draw_date: settings.draw_date || null,
            total_draws: totalDraws,
            revealed_count: revealedCount,
            pending_reveals: totalDraws - revealedCount,
            completion_percentage: totalDraws > 0 ? Math.round((revealedCount / totalDraws) * 100) : 0
        };
    },

    // Export data
    async exportData() {
        const [participants, draws, settings] = await Promise.all([
            this.getParticipants(),
            this.getDraw(),
            this.getSettings()
        ]);

        return {
            export_date: new Date().toISOString(),
            participants,
            draws,
            settings
        };
    },

    // Reset all data
    async resetAll() {
        const sb = await initSupabase();

        // Delete all draws
        await sb.from('draws').delete().neq('id', '00000000-0000-0000-0000-000000000000');

        // Delete all participants
        await sb.from('participants').delete().neq('id', '00000000-0000-0000-0000-000000000000');

        // Reset settings
        await this.setSetting('gift_value_rules', '');
        await this.setSetting('draw_generated', 'false');
        await this.setSetting('draw_date', '');

        return true;
    }
};

// Generate cryptographically secure token
function generateToken(length = 32) {
    const array = new Uint8Array(length / 2);
    crypto.getRandomValues(array);
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

// Export for use in other files
window.SupabaseAPI = SupabaseAPI;
window.initSupabase = initSupabase;
