import pandas as pd
import numpy as np
import re
import argparse
import csv
import datetime

pd.set_option('display.max_row',10)
pd.set_option('display.max_colwidth',225)

def find_dates_and_meters(args):
    dates_and_meters = []

    dateparse = lambda x: datetime.datetime.strptime(x, '%m/%d/%Y %H:%M:%S')

    df = pd.read_csv(args.input_file,
                     #nrows=400000,
                     parse_dates=['Date / Time'],
                     date_parser=dateparse,
                     converters={'Object Value': drop_units})

    # Remove non-numeric data values
    df.dropna( subset=['Object Value'], inplace=True )

    # Save date range
    min_date = df['Date / Time'].min().strftime( '%m/%d/%Y' )
    max_date = df['Date / Time'].max().strftime( '%m/%d/%Y' )
    dates_and_meters.append((min_date,max_date))

    # Organize data for meter analysis
    df.sort_values(by=['Object Name', 'Date / Time'], inplace=True)

    # Save meters
    for trend, frame in df.groupby('Object Name'):
        summary = check_summarizable(frame['Object Value'].values,args)
        dates_and_meters.append((trend,summary))

    return dates_and_meters


drop_units_pattern = re.compile( r"\A([+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?).*\Z" )

def drop_units(value):
    """Remove the units from a string, e.g. '309.2 kWh' -> 309.2"""
    match = drop_units_pattern.match(value)
    if match is None:
        # value was not of the expected format
        return np.nan
    else:
        return float(match.group(1))


def check_summarizable(series,args):
    if len( series ) < 2:
        return False

    boolseriesrising = ((series[1:] - series[:-1]) > 0)
    boolseriesconstant = ((series[1:] - series[:-1]) == 0)
    boolseriesfalling = ((series[1:] - series[:-1]) < 0)
    rising = boolseriesrising.mean()
    constant = boolseriesconstant.mean()
    falling = boolseriesfalling.mean()

    if constant > args.constant_threshold:
        return False
    elif rising / (rising + falling) > args.rising_threshold:
        return True
    else:
        return False


def write_dates_and_meters(array,output_file):
    with open(output_file, mode='w', newline="", encoding='utf-8') as w:
        csvwriter = csv.writer(w)
        for entry in array:
            csvwriter.writerow(entry)



if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='script to find meters in Metasys data files')
    parser.add_argument('-i', dest='input_file',  help='name of input file')
    parser.add_argument('-o', dest='output_file', help='name of output file')
    parser.add_argument('-c', dest='constant_threshold', type=float, help='threshold for detection of constant trend')
    parser.add_argument('-r', dest='rising_threshold', type=float, help='threshold for detection of rising meter')
    args = parser.parse_args()


    import time
    start_time = time.time()
    q = find_dates_and_meters(args)
    print( time.time() - start_time )
    write_dates_and_meters(q,args.output_file)
