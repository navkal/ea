import argparse
import pandas as pd
import numpy as np
import datetime
import re


def parse(input_file):
    """Parse a file generated by the Metasys application

    Args:
        input_file: string, indicating path of Metasys file

    Returns:
        pandas DataFrame, with timestamps as index and measurement variables as columns
    """
    # Read the file into a pandas Series with MultiIndex
    df = pd.read_csv(input_file,
                     index_col=[0, 2],
                     converters={'Object Value': drop_units})['Object Value']

    # Drop duplicate measurements
    df = df.groupby(df.index).last()
    df.index = pd.MultiIndex.from_tuples(df.index)

    # Transform df from 1-dimensional Series with MultiIndex to 2-dimensional DataFrame
    df = df.unstack(level=-1)

    # Convert index to DatetimeIndex
    df.index = pd.to_datetime(df.index)

    return df


def drop_units(value):
    """Remove the units from a string, e.g. '309.2 kWh' -> 309.2"""
    pattern = re.compile(r"\A(\d*\.?\d+) ?[a-zA-Z]*\Z")
    match = pattern.match(value)
    if match is None:
        # value was not of the expected format
        return np.nan
    else:
        return float(match.group(1))


def summarize(df, columns, start_time=None, end_time=None):
    """Return a table describing daily energy usage"""
    if end_time is not None and start_time is None:
        raise ValueError('end_time should not be specified unless start_time is specified')
    elif start_time is not None and end_time is not None:
        df = df.iloc[df.index.indexer_between_time(start_time, end_time),:]

    invalid_columns = set(columns) - set(df.columns)
    if invalid_columns:
        raise ValueError('the following column names were not found: {0}'.format(invalid_columns))
    subset = df[columns]

    # Sometimes measurements are incorrectly reported as 0
    subset = subset.replace(to_replace=0, value=np.nan)

    grouped_cumulative_energy = subset.groupby(group_key(subset, start_time, end_time))
    daily_energy = grouped_cumulative_energy.max() - grouped_cumulative_energy.min()

    return daily_energy


def group_key(df, start_time, end_time):
    """Return a key by which to group measurements"""
    if start_time is None and end_time is None:
        return df.index.date
    elif start_time is not None and end_time is None:
        # associate times before start_time with the previous day
        return df.index.shift(-1, _timedelta_since_midnight(start_time)).date
    elif end_time is not None:
        start_timedelta = _timedelta_since_midnight(start_time)
        end_timedelta = _timedelta_since_midnight(end_time)
        if start_timedelta < end_timedelta:
            # measurement is of daytime usage, so no need to shift
            return df.index.date
        else:
            # measurement is of nighttime usage, so need to associate times with dates wisely
            return df.index.shift(-1, 0.5 * (start_timedelta + end_timedelta)).date


def _timedelta_since_midnight(time):
    return datetime.timedelta(hours=time.hour, minutes=time.minute)


def _string_to_time(time_string):
    if time_string is None:
        return None
    else:
        hour, minute = map(int, time_string.split(':'))
        return datetime.time(hour=hour, minute=minute)


def header(start_time, end_time):
    """Return a header to print at the top of output files"""
    if end_time is None:
        if start_time is None:
            return 'Usage statistics from all hours of each day'
        else:
            return 'Usage statistics start at %s each day' % start_time
    else:
        return 'Usage statistics from between %s and %s each day' % (start_time, end_time)


def save_df(transformed, summarized, start_time, end_time, output_file):
    """Save input DataFrame to a csv file"""
    if summarized:
        with open(output_file, 'w') as output_file:
            output_file.write(header(start_time, end_time))
            output_file.write('\n')
            transformed.to_csv(output_file, mode='a')
    else:
        transformed.to_csv(output_file)


def _parse_columns_file(columns_file):
    with open(columns_file) as f:
        columns = [column.strip('\n') for column in f]
    return columns


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='script for parsing Metasys data files')
    parser.add_argument('-s', '--summarize', dest='summarize', action='store_true',
                        help='option indicating whether output file should be a summary')
    parser.add_argument('--start', dest='start_time', nargs='?',
                        help='start time for summary table')
    parser.add_argument('--end', dest='end_time', nargs='?', help='end time for summary table')
    parser.add_argument('-i', dest='input_file',  help='name of input file')
    parser.add_argument('-o', dest='output_file', help='name of output file')
    parser.add_argument('-c', dest='columns_file', nargs='?', help='name of header file')
    args = parser.parse_args()
    transformed = parse(args.input_file)

    if args.columns_file:
        columns = _parse_columns_file(args.columns_file)
    start_time = _string_to_time(args.start_time)
    end_time = _string_to_time(args.end_time)

    if args.summarize:
        transformed = summarize(transformed, columns, start_time, end_time)

    save_df(transformed, args.summarize, args.start_time, args.end_time, args.output_file)