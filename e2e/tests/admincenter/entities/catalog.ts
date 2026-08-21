import type { EntityCrudConfig, FieldFill } from './helpers';

/** Compact unique token: `C`/`E` + epoch millis (14 chars, fits most VARCHAR columns). */
export function createdToken(stamp: number): string {
  return `C${stamp}`;
}

export function editedToken(stamp: number): string {
  return `E${stamp}`;
}

function text(value: string): FieldFill {
  return { type: 'text', value };
}

function number(value: string): FieldFill {
  return { type: 'number', value };
}

function email(value: string): FieldFill {
  return { type: 'email', value };
}

function password(value: string): FieldFill {
  return { type: 'password', value };
}

function textarea(value: string): FieldFill {
  return { type: 'textarea', value };
}

function date(value: string): FieldFill {
  return { type: 'date', value };
}

function color(value: string): FieldFill {
  return { type: 'color', value };
}

function select(value: string): FieldFill {
  return { type: 'select', value };
}

function fk(value: string): FieldFill {
  return { type: 'fk', value };
}

function timestamp(dateValue: string, time: string): FieldFill {
  return { type: 'timestamp', date: dateValue, time };
}

function checkbox(checked: boolean): FieldFill {
  return { type: 'checkbox', checked };
}

/** Last 5 digits of the stamp — fits VARCHAR(5) short codes. */
function kurz(stamp: number, prefix: 'C' | 'E'): string {
  return `${prefix}${String(stamp).slice(-4)}`;
}

type EntityFactory = (stamp: number) => EntityCrudConfig;

/**
 * One factory per AdminCenter entity (module.xml `<adminpage entity="...">`).
 * Seed FKs: league=1, club=1/2, user=1/2, player=1, stadium=1, season=1, builder=1.
 */
export const entityFactories: Record<string, EntityFactory> = {
  admin: (stamp) => {
    const created = createdToken(stamp);
    return {
      id: 'admin',
      heading: 'Admin Users',
      uniqueField: 'name',
      createdLabel: created,
      editedLabel: editedToken(stamp),
      filterColumn: 'entity_admin_name',
      fields: {
        name: text(created),
        passwort: password('E2ePass123'),
        email: email(`${created}@e2e.test`),
      },
      editFields: {
        name: text(editedToken(stamp)),
        email: email(`${editedToken(stamp)}@e2e.test`),
      },
    };
  },

  users: (stamp) => {
    const created = createdToken(stamp);
    return {
      id: 'users',
      heading: 'User',
      uniqueField: 'nick',
      createdLabel: created,
      editedLabel: editedToken(stamp),
      filterColumn: 'entity_users_nick',
      fields: {
        nick: text(created),
        email: email(`${created}@e2e.test`),
        passwort: password('E2ePass123'),
        status: checkbox(true),
      },
      editFields: {
        nick: text(editedToken(stamp)),
        email: email(`${editedToken(stamp)}@e2e.test`),
      },
    };
  },

  news: (stamp) => {
    const created = createdToken(stamp);
    return {
      id: 'news',
      heading: 'News',
      uniqueField: 'titel',
      createdLabel: created,
      editedLabel: editedToken(stamp),
      filterColumn: 'entity_news_titel',
      fields: {
        autor_id: fk('1'),
        titel: text(created),
        datum: timestamp('2026-08-16', '12:00'),
        nachricht: textarea(`Created by the Playwright E2E suite (${created})`),
        status: checkbox(true),
      },
      editFields: { titel: text(editedToken(stamp)) },
    };
  },

  league: (stamp) => ({
    id: 'league',
    heading: 'League',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_league_name',
    fields: {
      name: text(createdToken(stamp)),
      kurz: text(kurz(stamp, 'C')),
      land: text('E2Eland'),
      p_steh: number('10'),
      p_sitz: number('10'),
      p_haupt_steh: number('10'),
      p_haupt_sitz: number('10'),
      p_vip: number('5'),
      preis_steh: number('7'),
      preis_sitz: number('12'),
      preis_vip: number('100'),
    },
    editFields: {
      name: text(editedToken(stamp)),
      kurz: text(kurz(stamp, 'E')),
    },
  }),

  club: (stamp) => ({
    id: 'club',
    heading: 'Club',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_club_name',
    fields: {
      name: text(createdToken(stamp)),
      kurz: text(kurz(stamp, 'C')),
      liga_id: fk('1'),
      finanz_budget: number('1000000'),
      preis_stehen: number('7'),
      preis_sitz: number('12'),
      preis_haupt_stehen: number('15'),
      preis_haupt_sitze: number('20'),
      preis_vip: number('50'),
      status: checkbox(true),
    },
    editFields: {
      name: text(editedToken(stamp)),
      kurz: text(kurz(stamp, 'E')),
    },
  }),

  player: (stamp) => ({
    id: 'player',
    heading: 'Soccer Player',
    uniqueField: 'nachname',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_player_nachname',
    fields: {
      vorname: text('E2E'),
      nachname: text(createdToken(stamp)),
      verein_id: fk('1'),
      geburtstag: date('1995-06-15'),
      position: select('Mittelfeld'),
      position_main: select('ZM'),
      nation: text('England'),
      w_staerke: number('50'),
      w_technik: number('50'),
      w_kondition: number('50'),
      w_frische: number('50'),
      w_zufriedenheit: number('50'),
      vertrag_gehalt: number('10000'),
      vertrag_spiele: number('20'),
      vertrag_torpraemie: number('1000'),
      status: checkbox(true),
    },
    editFields: { nachname: text(editedToken(stamp)) },
  }),

  stadium: (stamp) => ({
    id: 'stadium',
    heading: 'Stadium',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_stadium_name',
    fields: {
      name: text(createdToken(stamp)),
      land: text('England'),
      p_steh: number('1000'),
      p_sitz: number('1000'),
      p_haupt_steh: number('500'),
      p_haupt_sitz: number('500'),
      p_vip: number('50'),
      level_pitch: number('1'),
      level_videowall: number('1'),
      level_seatsquality: number('1'),
      level_vipquality: number('1'),
      maintenance_pitch: number('0'),
      maintenance_videowall: number('0'),
      maintenance_seatsquality: number('0'),
      maintenance_vipquality: number('0'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  stadiumbuilder: (stamp) => ({
    id: 'stadiumbuilder',
    heading: 'Stadium Builder',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_stadiumbuilder_name',
    fields: {
      name: text(createdToken(stamp)),
      fixedcosts: number('1000'),
      cost_per_seat: number('10'),
      construction_time_days: number('5'),
      construction_time_days_min: number('3'),
      min_stadium_size: number('0'),
      max_stadium_size: number('50000'),
      reliability: number('90'),
      premiumfee: number('0'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  stadiumconstruction: (stamp) => ({
    id: 'stadiumconstruction',
    heading: 'Stadium Extension',
    uniqueField: 'started',
    createdLabel: '15.01.2099, 10:00',
    editedLabel: '15.01.2099, 11:00',
    fields: {
      team_id: fk('40'),
      builder_id: fk('1'),
      started: timestamp('2099-01-15', '10:00'),
      deadline: timestamp('2099-03-15', '10:00'),
      p_steh: number('100'),
      p_sitz: number('100'),
      p_haupt_steh: number('0'),
      p_haupt_sitz: number('0'),
      p_vip: number('0'),
    },
    editFields: {
      started: timestamp('2099-01-15', '11:00'),
    },
  }),

  sponsor: (stamp) => ({
    id: 'sponsor',
    heading: 'Sponsor',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_sponsor_name',
    fields: {
      name: text(createdToken(stamp)),
      liga_id: fk('1'),
      b_spiel: number('1000'),
      b_heimzuschlag: number('100'),
      b_sieg: number('2000'),
      b_meisterschaft: number('10000'),
      max_teams: number('3'),
      min_platz: number('1'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  trainer: (stamp) => ({
    id: 'trainer',
    heading: 'Trainer',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_trainer_name',
    fields: {
      name: text(createdToken(stamp)),
      salary: number('5000'),
      p_technique: number('40'),
      p_stamina: number('40'),
      premiumfee: number('0'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  trainingcamp: (stamp) => ({
    id: 'trainingcamp',
    heading: 'Training Camp',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_trainingcamp_name',
    fields: {
      name: text(createdToken(stamp)),
      land: text('Spain'),
      preis_spieler_tag: number('500'),
      p_staerke: number('1'),
      p_technik: number('1'),
      p_kondition: number('1'),
      p_frische: number('1'),
      p_zufriedenheit: number('1'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  tablemarker: (stamp) => ({
    id: 'tablemarker',
    heading: 'Table Marker',
    uniqueField: 'bezeichnung',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_tablemarker_bezeichnung',
    fields: {
      bezeichnung: text(createdToken(stamp)),
      liga_id: fk('1'),
      platz_von: number('1'),
      platz_bis: number('2'),
      farbe: color('#123456'),
    },
    editFields: { bezeichnung: text(editedToken(stamp)) },
  }),

  season: (stamp) => ({
    id: 'season',
    heading: 'Season',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_season_name',
    fields: {
      name: text(createdToken(stamp)),
      liga_id: fk('1'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  cup: (stamp) => ({
    id: 'cup',
    heading: 'Cup',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_cup_name',
    fields: {
      name: text(createdToken(stamp)),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  match: (stamp) => ({
    id: 'match',
    heading: 'Match',
    uniqueField: 'pokalname',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_match_pokalname',
    fields: {
      datum: timestamp('2026-09-01', '15:00'),
      spieltyp: select('Freundschaft'),
      pokalname: text(createdToken(stamp)),
      home_verein: fk('1'),
      gast_verein: fk('2'),
    },
    editFields: { pokalname: text(editedToken(stamp)) },
  }),

  matchtext: (stamp) => ({
    id: 'matchtext',
    heading: 'Match Report Message',
    uniqueField: 'nachricht',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_matchtext_nachricht',
    fields: {
      aktion: select('Tor'),
      nachricht: text(createdToken(stamp)),
    },
    editFields: { nachricht: text(editedToken(stamp)) },
  }),

  youthplayer: (stamp) => ({
    id: 'youthplayer',
    heading: 'Youth Player',
    uniqueField: 'lastname',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_youthplayer_lastname',
    fields: {
      team_id: fk('1'),
      firstname: text('E2E'),
      lastname: text(createdToken(stamp)),
      age: number('16'),
      position: select('Mittelfeld'),
      nation: text('England'),
      strength: number('40'),
    },
    editFields: { lastname: text(editedToken(stamp)) },
  }),

  youthscout: (stamp) => ({
    id: 'youthscout',
    heading: 'Talent Scout',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_youthscout_name',
    fields: {
      name: text(createdToken(stamp)),
      expertise: number('70'),
      fee: number('5000'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  youthmatch: (stamp) => ({
    id: 'youthmatch',
    heading: 'Youth Match',
    uniqueField: 'matchdate',
    createdLabel: '01.04.2099, 14:00',
    editedLabel: '01.04.2099, 15:00',
    fields: {
      matchdate: timestamp('2099-04-01', '14:00'),
      home_team_id: fk('1'),
      guest_team_id: fk('2'),
    },
    editFields: {
      matchdate: timestamp('2099-04-01', '15:00'),
    },
  }),

  stadiumbuilding: (stamp) => ({
    id: 'stadiumbuilding',
    heading: 'Buildings',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_stadiumbuilding_name',
    fields: {
      name: text(createdToken(stamp)),
      costs: number('25000'),
      construction_time_days: number('5'),
      premiumfee: number('0'),
      effect_training: number('0'),
      effect_youthscouting: number('0'),
      effect_tickets: number('0'),
      effect_fanpopularity: number('0'),
      effect_injury: number('0'),
      effect_income: number('0'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  badge: (stamp) => ({
    id: 'badge',
    heading: 'Badges',
    uniqueField: 'name',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    fields: {
      name: text(createdToken(stamp)),
      level: select('bronze'),
      event: select('cupwinner'),
      event_benchmark: number('1'),
    },
    editFields: { name: text(editedToken(stamp)) },
  }),

  randomevent: (stamp) => ({
    id: 'randomevent',
    heading: 'Random Events',
    uniqueField: 'message',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    fields: {
      message: text(createdToken(stamp)),
      effect: select('money'),
      effect_money_amount: number('1000'),
      weight: number('1'),
    },
    editFields: { message: text(editedToken(stamp)) },
  }),

  userabsence: (stamp) => ({
    id: 'userabsence',
    heading: 'Absences',
    uniqueField: 'from_date',
    createdLabel: '01.07.2099, 08:00',
    editedLabel: '01.07.2099, 09:00',
    fields: {
      user_id: fk('5'),
      from_date: timestamp('2099-07-01', '08:00'),
      to_date: timestamp('2099-07-10', '18:00'),
      deputy_id: fk('4'),
    },
    editFields: {
      from_date: timestamp('2099-07-01', '09:00'),
    },
  }),

  transaction: (stamp) => ({
    id: 'transaction',
    heading: 'Money Transactions',
    uniqueField: 'verwendung',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    fields: {
      verein_id: fk('1'),
      datum: timestamp('2026-08-01', '12:00'),
      absender: text('E2E'),
      verwendung: text(createdToken(stamp)),
      betrag: number('100'),
    },
    editFields: { verwendung: text(editedToken(stamp)) },
  }),

  premiumstatement: (stamp) => ({
    id: 'premiumstatement',
    heading: 'Premium Transactions',
    uniqueField: 'action_id',
    createdLabel: createdToken(stamp),
    editedLabel: editedToken(stamp),
    filterColumn: 'entity_premiumstatement_action_id',
    fields: {
      user_id: fk('1'),
      created_date: timestamp('2026-08-01', '12:00'),
      action_id: text(createdToken(stamp)),
      amount: number('10'),
    },
    editFields: { action_id: text(editedToken(stamp)) },
  }),

  premiumpayment: (stamp) => ({
    id: 'premiumpayment',
    heading: 'Money Payments',
    uniqueField: 'created_date',
    createdLabel: '20.08.2099, 16:30',
    editedLabel: '20.08.2099, 17:30',
    fields: {
      user_id: fk('5'),
      created_date: timestamp('2099-08-20', '16:30'),
      amount: number('99'),
    },
    editFields: {
      created_date: timestamp('2099-08-20', '17:30'),
    },
  }),

  transfer_offer: (stamp) => ({
    id: 'transfer_offer',
    heading: 'Transfer Offer',
    uniqueField: 'submitted_date',
    createdLabel: '01.05.2099, 12:00',
    editedLabel: '01.05.2099, 13:00',
    fields: {
      submitted_date: timestamp('2099-05-01', '12:00'),
      player_id: fk('1'),
      receiver_club_id: fk('1'),
      sender_club_id: fk('2'),
      sender_user_id: fk('2'),
      offer_amount: number('250000'),
      offer_message: text(createdToken(stamp)),
    },
    editFields: {
      submitted_date: timestamp('2099-05-01', '13:00'),
    },
  }),

  transfers: (stamp) => ({
    id: 'transfers',
    heading: 'Player Transfers',
    uniqueField: 'datum',
    createdLabel: '01.06.2099, 12:00',
    editedLabel: '01.06.2099, 13:00',
    fields: {
      datum: timestamp('2099-06-01', '12:00'),
      spieler_id: fk('1'),
      seller_club_id: fk('2'),
      buyer_club_id: fk('1'),
      directtransfer_amount: number('250000'),
    },
    editFields: {
      datum: timestamp('2099-06-01', '13:00'),
    },
  }),
};

export const entityIds = Object.keys(entityFactories);
