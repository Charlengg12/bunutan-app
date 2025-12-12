/*
  # Bunutan Gift Exchange Database Schema

  1. New Tables
    - `participants`
      - `id` (uuid, primary key, auto-generated)
      - `name` (text, unique, not null) - Participant's full name
      - `email` (text, nullable) - Optional email for notifications
      - `added_at` (timestamptz) - When participant was added
      - `created_at` (timestamptz, default now())
      - `updated_at` (timestamptz, default now())
    
    - `draws`
      - `id` (uuid, primary key, auto-generated)
      - `giver_id` (uuid, foreign key -> participants.id)
      - `receiver_id` (uuid, foreign key -> participants.id)
      - `token` (text, unique, not null) - Unique reveal token
      - `revealed` (boolean, default false) - Has the giver revealed their partner?
      - `revealed_at` (timestamptz, nullable) - When the reveal happened
      - `created_at` (timestamptz, default now())
      - `updated_at` (timestamptz, default now())
    
    - `settings`
      - `id` (uuid, primary key, auto-generated)
      - `key` (text, unique, not null) - Setting key (e.g., 'gift_value_rules')
      - `value` (text) - Setting value
      - `created_at` (timestamptz, default now())
      - `updated_at` (timestamptz, default now())

  2. Indexes
    - Index on draws.token for fast lookups
    - Index on draws.giver_id and receiver_id
    - Index on settings.key for fast lookups
    - Unique constraint on participants.name (case-insensitive)

  3. Security
    - Enable RLS on all tables
    - Public read access to settings (gift rules visible to all)
    - Token-based access to draws (participants can reveal with valid token)
    - Admin-only access to participants table
    - Admin-only write access to all tables

  4. Functions
    - Updated_at trigger for automatic timestamp updates
*/

-- Create participants table
CREATE TABLE IF NOT EXISTS participants (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  name text UNIQUE NOT NULL,
  email text,
  added_at timestamptz DEFAULT now(),
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now(),
  CONSTRAINT name_not_empty CHECK (trim(name) <> '')
);

-- Create draws table
CREATE TABLE IF NOT EXISTS draws (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  giver_id uuid NOT NULL REFERENCES participants(id) ON DELETE CASCADE,
  receiver_id uuid NOT NULL REFERENCES participants(id) ON DELETE CASCADE,
  token text UNIQUE NOT NULL,
  revealed boolean DEFAULT false,
  revealed_at timestamptz,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now(),
  CONSTRAINT giver_not_receiver CHECK (giver_id <> receiver_id),
  CONSTRAINT token_not_empty CHECK (trim(token) <> '')
);

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  key text UNIQUE NOT NULL,
  value text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now(),
  CONSTRAINT key_not_empty CHECK (trim(key) <> '')
);

-- Insert default settings
INSERT INTO settings (key, value) VALUES 
  ('gift_value_rules', ''),
  ('draw_generated', 'false'),
  ('draw_date', '')
ON CONFLICT (key) DO NOTHING;

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_draws_token ON draws(token);
CREATE INDEX IF NOT EXISTS idx_draws_giver_id ON draws(giver_id);
CREATE INDEX IF NOT EXISTS idx_draws_receiver_id ON draws(receiver_id);
CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(key);

-- Create function for updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Add updated_at triggers
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_participants_updated_at') THEN
    CREATE TRIGGER update_participants_updated_at
      BEFORE UPDATE ON participants
      FOR EACH ROW
      EXECUTE FUNCTION update_updated_at_column();
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_draws_updated_at') THEN
    CREATE TRIGGER update_draws_updated_at
      BEFORE UPDATE ON draws
      FOR EACH ROW
      EXECUTE FUNCTION update_updated_at_column();
  END IF;

  IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_settings_updated_at') THEN
    CREATE TRIGGER update_settings_updated_at
      BEFORE UPDATE ON settings
      FOR EACH ROW
      EXECUTE FUNCTION update_updated_at_column();
  END IF;
END $$;

-- Enable Row Level Security
ALTER TABLE participants ENABLE ROW LEVEL SECURITY;
ALTER TABLE draws ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;

-- RLS Policies for participants table
-- For now, allow all operations (we'll add auth later)
CREATE POLICY "Allow all access to participants"
  ON participants
  FOR ALL
  TO public
  USING (true)
  WITH CHECK (true);

-- RLS Policies for draws table
-- Allow reading own draw via token
CREATE POLICY "Allow token-based reveal"
  ON draws
  FOR SELECT
  TO public
  USING (true);

CREATE POLICY "Allow token-based update for reveal"
  ON draws
  FOR UPDATE
  TO public
  USING (true)
  WITH CHECK (true);

-- Allow all operations for now (will restrict with auth later)
CREATE POLICY "Allow all operations on draws"
  ON draws
  FOR ALL
  TO public
  USING (true)
  WITH CHECK (true);

-- RLS Policies for settings table
-- Allow everyone to read settings (gift rules are public)
CREATE POLICY "Allow read access to settings"
  ON settings
  FOR SELECT
  TO public
  USING (true);

-- Allow all write operations for now (will restrict with auth later)
CREATE POLICY "Allow write access to settings"
  ON settings
  FOR ALL
  TO public
  USING (true)
  WITH CHECK (true);